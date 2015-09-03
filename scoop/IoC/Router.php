<?php
namespace Scoop\IoC;

class Router
{
    private $routes = array();
    private $instances = array();
    private static $route;

    public function __construct($fileName)
    {
        $routes = require $fileName.'.php';
        array_walk($routes, array($this, 'load'));
    }

    public function route($url)
    {
        self::$route = $url;
        $matches = array_filter($this->routes, array($this, 'filterRoute'));

        if ($matches) {
            usort($matches, array($this, 'sortByURL'));
            $route = array_pop($matches);
            if (isset($route['controller'])) {
                $controller = explode(':', $route['controller']);
                $method = array_pop($controller);
                $controller = array_shift($controller);

                if (get_parent_class($controller) !== 'Scoop\Controller') {
                    throw new \Exception('The '.$controller.' class is not an instance of controller');
                }
                $controller =  $this->getInstance($controller);
                $controller->setRouter($this);

                if ($controller) {
                    array_shift($route['params']);
                    $params = array_filter($route['params']);
                    $params = array_map(array($this, 'formatParam'), $params);
                    $controllerReflection = new \ReflectionClass($controller);
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
        $count = count($path)-1;
        $url = '';

        for ($i=0; $i<$count; $i++) {
            $url .= urlencode($params[$i]).$path[$i];
        }
        return $url;
    }

    public function intercept($url)
    {
        self::$route = $url;
        $matches = array_filter($this->routes, array($this, 'filterInterceptor'));

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
        $url = $oldURL.$route['url'];

        if (isset($route['routes'])) {
            array_walk($route['routes'], array($this, 'load'), $url);
            unset($route['routes']);
        }
        $this->routes[$key] = $route;
    }

    private static function sortByURL($a, $b)
    {
        return $a['url'] - $b['url'];
    }

    private static function filterRoute(&$route)
    {
        return preg_match('/^'.self::normalizeURL($route['url']).'$/', self::$route, $route['params']);
    }

    private static function filterInterceptor(&$route)
    {

        return preg_match('/^'.self::normalizeURL($route['url']).'/', self::$route);
    }

    private static function normalizeURL($url)
    {
        $url = str_replace(array(
            '/[var]',
            '/[int]'
        ), array(
            '(/?[\w\s]*)',
            '(/?\d*)'
        ), $url);
        return addcslashes($url, '/');
    }

    private static function formatParam($param)
    {
        return substr($param, 1);
    }
}
