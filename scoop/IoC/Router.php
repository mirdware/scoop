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
        $matches = $this->filter($url);

        if ($matches) {
            usort($matches, array($this, 'sortByURL'));
            $route = array_pop($matches);
            $method = explode(':', $route['controller']);
            $controller = array_shift($method);

            if (get_parent_class($controller) !== 'Scoop\Controller') {
                throw new \UnexpectedValueException($controller.' class isn\'t an instance of \Scoop\Controller');
            }
            $controller =  $this->getInstance($controller);
            $controller->setRouter($this);

            if ($controller) {
                $this->intercept($url);
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
        throw new \Scoop\Http\NotFoundException();
    }

    public function intercept($url)
    {
        $matches = $this->filterProxy($url);

        if ($matches) {
            usort($matches, array($this, 'sortByURL'));
            foreach ($matches as &$route) {
                if (isset($route['proxy'])) {
                    $proxy = explode(':', $route['proxy']);
                    $method = array_pop($proxy);
                    $proxy = array_shift($proxy);
                    $proxy =  $this->getInstance($proxy);
                    $proxy->$method();
                }
            }
        }
    }

    public function filter($url)
    {
        $matches = array();
        foreach ($this->routes as &$route) {
            if (preg_match('/^'.self::normalizeURL($route['url']).'$/', $url, $route['params'])) {
                $matches[] = $route;
            }
        }
        return $matches;
    }

    public function getInstance($class)
    {
        if (!isset($this->instances[$class])) {
            $this->instances[$class] = Injector::create($class);
        }
        return $this->instances[$class];
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

    private function filterProxy($url)
    {
        $matches = array();
        foreach ($this->routes as &$route) {
            if (preg_match('/^'.self::normalizeURL($route['url']).'/', $url)) {
                $matches[] = $route;
            }
        }
        return $matches;
    }

    private function load($route, $key, $oldURL = '')
    {
        if (!isset($route['controller'])) {
            throw new \OutOfBoundsException('The controller\'s key has not been defined for the route '.$route['url']);
        }
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

    private static function normalizeURL($url)
    {
        $url = str_replace(
            array('{var}', '{int}'),
            array('([\w\+\-\s\.]*)', '(\d*)'),
            $url);
        if (substr($url, -1) !== '/') {
            $url .= '/';
        }
        return addcslashes($url, '/');
    }
}
