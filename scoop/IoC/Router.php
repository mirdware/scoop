<?php
namespace Scoop\IoC;

class Router
{
    private $routes = array();
    private $instances = array();
    private $current;

    public function __construct($routes)
    {
        foreach ($routes as $key => &$route) {
            $this->load($route, $key);
        }
        uasort($this->routes, array($this, 'sortByURL'));
    }

    public function route($url)
    {
        $route = $this->getRoute($url);
        if ($route) {
            $method = explode(':', $route['controller']);
            $controller = array_shift($method);
            if (get_parent_class($controller) !== 'Scoop\Controller') {
                throw new \UnexpectedValueException(
                    $controller.' class isn\'t an instance of \Scoop\Controller'
                );
            }
            $controller =  $this->getInstance($controller);
            $controller->setRouter($this);
            if ($controller) {
                $this->intercept($url);
                $params = &$route['params'];
                $controllerReflection = new \ReflectionClass($controller);
                $method = $controllerReflection->implementsInterface('Scoop\Http\Resource')?
                    strtolower($_SERVER['REQUEST_METHOD']):
                    array_shift($method);
                if (!$controllerReflection->hasMethod($method)) {
                    throw new \Scoop\Http\MethodNotAllowedException();
                }
                $method = $controllerReflection->getMethod($method);
                $numParams = count($params);
                if (
                    $numParams >= $method->getNumberOfRequiredParameters() &&
                    $numParams <= $method->getNumberOfParameters()
                ) {
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
        return ROOT.substr($url, 1).'/';
    }

    public function getCurrentRoute()
    {
        return $this->current;
    }

    private function getRoute($url)
    {
        $matches = $this->filterRoute($url);
        if ($matches) {
            $route = end($matches);
            $this->current = key($matches);
            array_shift($route['params']);
            $key = 0;
            foreach ($route['params'] as $key => &$param) {
                if ($param !== '') {
                    $param = urldecode($param);
                }
            }
            $route['params'] = array_splice($route['params'], 0, ++$key);
            return $route;
        }
    }

    private function filterRoute($url)
    {
        $matches = array();
        foreach ($this->routes as $key => &$route) {
            if (
                isset($route['controller']) &&
                preg_match('/^'.self::normalizeURL($route['url']).'$/', $url, $route['params'])
            ) {
                $matches[$key] = $route;
            }
        }
        return $matches;
    }

    private function filterProxy($url)
    {
        $matches = array();
        foreach ($this->routes as &$route) {
            if (
                isset($route['proxy']) &&
                preg_match('/^'.self::normalizeURL($route['url']).'/', $url)
            ) {
                $matches[] = $route;
            }
        }
        return $matches;
    }

    private function load($route, $key, $oldURL = '')
    {
        if (!isset($route['url'])) {
            throw new \OutOfBoundsException('url\'s key has not been defined for the route');
        }
        $route['url'] = $oldURL.$route['url'];
        if (isset($route['routes'])) {
            foreach ($route['routes'] as $k => &$r) {
                $this->load($r, $k, $route['url']);
            }
            unset($route['routes']);
        }
        $this->routes[$key] = $route;
    }

    private static function sortByURL($a, $b)
    {
        $a = self::skipParams($a['url']);
        $b = self::skipParams($b['url']);
        return strcasecmp($a, $b) > 0;
    }

    private static function skipParams($url)
    {
        return str_replace(array('{var}', '{int}'), '', $url);
    }

    private static function normalizeURL($url)
    {
        $url = str_replace(array(
            '/{var}/',
            '/{int}/',
            '{var}',
            '{int}'
        ), array(
            '/([\w\+\-\s\.]*)/?',
            '/(\d*)/?',
            '([\w\+\-\s\.]*)',
            '(\d*)'
        ),$url).((substr($url, -1) === '/')? '?': '/?');
        return addcslashes($url, '/');
    }
}
