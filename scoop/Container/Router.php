<?php

namespace Scoop\Container;

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

    public function getURL($key, $params, $query)
    {
        $path = preg_split('/\[\w+\]/', $this->routes[$key]['url']);
        $url = array_shift($path);
        $count = count($path);
        if (count($params) > $count) {
            throw new \InvalidArgumentException('Unformed URL');
        }
        for ($i = 0; $i < $count; $i++) {
            if (isset($params[$i])) {
                $url .= self::encodeURL(trim($params[$i])) . $path[$i];
            }
        }
        if (strrpos($url, '/') !== strlen($url) - 1) {
            $url .= '/';
        }
        return ROOT . substr($url, 1) . $this->formatQueryString($query);
    }

    public function formatQueryString($query)
    {
        if (!is_array($query)) {
            return '';
        }
        $queryString = '';
        foreach ($query as $key => $value) {
            if ($value) {
                $queryString .= '&' . filter_var($key, FILTER_UNSAFE_RAW) . '=' . filter_var($value, FILTER_UNSAFE_RAW);
            }
        }
        return $queryString ? '?' . substr($queryString, 1) : '';
    }

    public function getCurrentRoute()
    {
        return $this->current;
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
        foreach ($this->routes as $routeDefinition) {
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
                return $routeDefinition;
            }
        }
    }

    private static function encodeURL($str)
    {
        $str = str_replace(
            array('á', 'à', 'ä', 'â', 'ª', 'Á', 'À', 'Â', 'Ä'),
            'a',
            $str
        );
        $str = str_replace(
            array('é', 'è', 'ë', 'ê', 'É', 'È', 'Ê', 'Ë'),
            'e',
            $str
        );
        $str = str_replace(
            array('í', 'ì', 'ï', 'î', 'Í', 'Ì', 'Ï', 'Î'),
            'i',
            $str
        );
        $str = str_replace(
            array('ó', 'ò', 'ö', 'ô', 'Ó', 'Ò', 'Ö', 'Ô'),
            'o',
            $str
        );
        $str = str_replace(
            array('ú', 'ù', 'ü', 'û', 'Ú', 'Ù', 'Û', 'Ü'),
            'u',
            $str
        );
        $str = str_replace(
            array(' ', 'ñ', 'Ñ', 'ç', 'Ç'),
            array('-', 'n', 'N', 'c', 'C'),
            $str
        );
        return urlencode($str);
    }
}
