<?php

namespace Scoop\Http;

class Router
{
    private $tree;
    private $routes;
    private $current;

    public function __construct(\Scoop\Bootstrap\Scanner\Route $scanner)
    {
        if (DEBUG_MODE) $scanner->scan();
        $routes = require $scanner->getCacheFilePath();
        $this->routes = $routes['map'];
        $this->tree = $routes['tree'];
    }

    public function route(\Scoop\Http\Message\Server\Request $request)
    {
        $route = $this->getRoute($request->getPath());
        if ($route) {
            $this->current = new \Scoop\Http\Message\Server\Route($route['id']);
            $this->current = $this->current
            ->withParameters($route['params'])
            ->withQuery($request->getQueryParams());
            if ($route['validator']) {
                $this->validateRoute($route['validator'], $route['params']);
            }
            $controller = $route['controller'];
            $method = $request->getMethod();
            if (is_array($controller) && isset($controller[$method])) {
                $controller = $controller[$method];
                if (method_exists($controller, '__invoke')) {
                    $method = '__invoke';
                }
            }
            $requestHandler = new \Scoop\Middleware\RequestHandler(
                $controller,
                $method,
                $route['middlewares'],
                $route['params'],
                new Transformer()
            );
            return $requestHandler->handle($request);
        }
        throw new \Scoop\Http\Exception\NotFound();
    }

    public function getCurrentRoute()
    {
        return $this->current;
    }

    public function getURL(\Scoop\Http\Message\Server\Route $route)
    {
        return $route->generateURL($this->routes);
    }

    public function getPath($id)
    {
        if (isset($this->routes[$id])) {
            return $this->routes[$id]['url'];
        }
        return null;
    }

    private function validateRoute($validatorName, $params)
    {
        if (!is_subclass_of($validatorName, '\Scoop\Validator')) {
            throw new \RuntimeException("Validator $validatorName not supported");
        }
        $validator = \Scoop\Context::inject($validatorName);
        if (!$validator->validate($params)) {
            throw new \Scoop\Http\Exception\NotFound();
        }
    }

    private function getRoute($url)
    {
        $path = trim($url, '/');
        $segments = $path === '' ? array() : explode('/', $path);
        $result = $this->match($this->tree, $segments, 0, array());
        if (!$result) return;
        $id = $result[0]['id'];
        return array_merge(array(
            'id' => $id,
            'params' => $result[1]
        ), $this->routes[$id]);
    }

    private function match($node, $segments, $index, $params)
    {
        if ($index === count($segments)) {
            return isset($node['id']) ? array($node, $params) : null;
        }
        $segment = $segments[$index];
        if (isset($node['s'][$segment])) {
            $result = $this->match($node['s'][$segment], $segments, $index + 1, $params);
            if ($result !== null) {
                return $result;
            }
        }
        if (isset($node['d'])) {
            $withParam = $params;
            $withParam[$node['d']['p']] = urldecode($segment);
            $result = $this->match($node['d'], $segments, $index + 1, $withParam);
            if ($result !== null) {
                return $result;
            }
        }
        return null;
    }
}
