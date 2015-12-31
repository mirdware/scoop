<?php
namespace Scoop\IoC;

class Router
{
    private $routes = array();
    private $instances = array();

    public function __construct($fileName)
    {
        $routes = require $fileName.'.php';
        array_walk($routes, array($this, 'load'));
    }

    public function route($url)
    {
        $matches = self::filterRoute($this->routes, $url);

        if ($matches) {
            usort($matches, array($this, 'sortByURL'));
            $route = array_pop($matches);
            if (isset($route['controller'])) {
                $method = explode(':', $route['controller']);
                $controller = array_shift($method);

                if (get_parent_class($controller) !== 'Scoop\Controller') {
                    throw new \UnexpectedValueException($controller.' class isn\'t an instance of \Scoop\Controller');
                }
                $controller =  $this->getInstance($controller);
                $controller->setRouter($this);

                if ($controller) {
                    array_shift($route['params']);
                    $params = array_filter($route['params']);
                    $controllerReflection = new \ReflectionClass($controller);
                    $interfaces = $controllerReflection->getInterfaces();
                    $method = isset($interfaces['Scoop\Http\Resource'])?
                    		strtolower($_SERVER['REQUEST_METHOD']):
                    		array_shift($method);
                    $method = $controllerReflection->getMethod($method);
                    $numParams = count($params);

                    if ($numParams >= $method->getNumberOfRequiredParameters() && $numParams <= $method->getNumberOfParameters()) {
                        return $method->invokeArgs($controller, $params);
                    }
                }
            }
        }
        throw new \Scoop\Http\NotFoundException();
    }

    public function getURL($key, $params)
    {
        $path = preg_split('/\[\w+\]/', $this->routes[$key]['url']);
        $url = array_shift($path);
        $count = count($path);

        for ($i=0; $i<$count; $i++) {
            if (isset($params[$i])) {
                $url .= urlencode($params[$i]).$path[$i];
            }
        }
        return ROOT.substr($url, 1);
    }

    public function intercept($url)
    {
        $matches = self::filterInterceptor($this->routes, $url);

        if ($matches) {
            usort($matches, array($this, 'sortByURL'));
            foreach ($matches as &$route) {
                if (isset($route['interceptor'])) {
                    $interceptor = explode(':', $route['interceptor']);
                    $method = array_pop($interceptor);
                    $interceptor = array_shift($interceptor);
                    $interceptor =  $this->getInstance($interceptor);
                    $interceptor->$method();
                }
            }
        }
    }

    public function getInstance($class)
    {
        if (!isset($this->instances[$class])) {
            $this->instances[$class] = Injector::create($class);
        }
        return $this->instances[$class];
    }

    private function load($route, $key, $oldURL = '')
    {
        $route['url'] = $oldURL.$route['url'];
        if (isset($route['routes'])) {
            array_walk($route['routes'], array($this, 'load'), $route['url']);
            unset($route['routes']);
        }
        $this->routes[$key] = $route;
    }

    private static function sortByURL($a, $b)
    {
        return strcmp($a['url'], $b['url']) > 0;
    }

    private static function filterRoute($routes, $url)
    {
        $matches = array();
        foreach ($routes as &$route) {
            if (preg_match('/^'.self::normalizeURL($route['url']).'$/', $url, $route['params'])) {
                $matches[] = $route;
            }
        }
        return $matches;
    }

    private static function filterInterceptor($routes, $url)
    {
        $matches = array();
        foreach ($routes as &$route) {
            if (preg_match('/^'.self::normalizeURL($route['url']).'/', $url)) {
                $matches[] = $route;
            }
        }
        return $matches;
    }

    private static function normalizeURL($url)
    {
        $url = str_replace(
            array('{var}', '{int}'),
            array('([\w\+\-\s\.]*)', '(\d*)'),
            $url);
        return addcslashes($url, '/');
    }
}
