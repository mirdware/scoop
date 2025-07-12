<?php

namespace Scoop\Http;

class Router
{
    private $routes;
    private $current;

    public function __construct(\Scoop\Bootstrap\Scanner\Route $scanner)
    {
        if (DEBUG_MODE) $scanner->scan();
        $this->routes = require $scanner->getCacheFilePath();
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
            $method = $request->getMethod();
            $controller = $this->getController($route['controller'], $method);
            if ($controller) {
                $controllerReflection = new \ReflectionClass($controller);
                if (is_callable($controller)) {
                    $method = '__invoke';
                } elseif (!$controllerReflection->hasMethod($method)) {
                    throw new \Scoop\Http\Exception\MethodNotAllowed("not implement $method method");
                }
                $callable = $controllerReflection->getMethod($method);
                $requestHandler = new \Scoop\Http\Handler\Request($controller, $callable, $route);
                return $requestHandler->handle($request);
            }
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

    private function getController($controller, $method)
    {
        if (is_array($controller)) {
            if (!isset($controller[$method])) {
                throw new \Scoop\Http\Exception\MethodNotAllowed("not implement $method method");
            }
            $controller = $controller[$method];
        }
        if (!class_exists($controller)) {
            throw new \Scoop\Http\Exception\NotFound();
        }
        return \Scoop\Context::inject($controller);
    }

    private function getRoute($url)
    {
        foreach ($this->routes as $key => $routeDefinition) {
            $urlPattern = $routeDefinition['url'];
            $regex = preg_quote($urlPattern, '#');
            $regex = preg_replace('/\\\\\[(\w+)\\\\\]/', '([^/]+)', $regex);
            if (preg_match("#^$regex$#", $url, $matches)) {
                $routeDefinition['params'] = array();
                preg_match_all('/\[(\w+)\]/', $urlPattern, $paramNames);
                $numParams = count($paramNames[1]);
                for ($i = 0; $i < $numParams; $i++) {
                    if (isset($matches[$i + 1])) {
                        $routeDefinition['params'][$paramNames[1][$i]] = urldecode($matches[$i + 1]);
                    }
                }
                $routeDefinition['id'] = $key;
                return $routeDefinition;
            }
        }
    }
}
