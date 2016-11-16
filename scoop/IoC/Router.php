<?php
namespace Scoop\IoC;

class Router
{
    private $routes = array();
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
            if (!is_subclass_of($controller, 'Scoop\Controller')) {
                throw new \UnexpectedValueException(
                    $controller.' class isn\'t an instance of \Scoop\Controller'
                );
            }
            $controller =  \Scoop\IoC\Injector::getInstance($controller);
            if ($controller) {
                $this->intercept($url);
                $params = &$route['params'];
                $controllerReflection = new \ReflectionClass($controller);
                $method = !$method && $controllerReflection->implementsInterface('Scoop\Http\Resource')?
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
                    $proxy =  \Scoop\IoC\Injector::getInstance($proxy);
                    $proxy->$method();
                }
            }
        }
    }

    public function getURL($key, $params)
    {
        $path = preg_split('/\{\w+\}/', $this->routes[$key]['url']);
        $url = array_shift($path);
        $count = count($path);
        if (count($params) > $count) {
            throw new \InvalidArgumentException('Unformed URL');
        }
        for ($i=0; $i<$count; $i++) {
            if (isset($params[$i])) {
                $url .= self::encodeURL($params[$i]).$path[$i];
            }
        }
        if (strrpos($url, '/') !== strlen($url)-1) {
            $url .= '/';
        }
        return ROOT.substr($url, 1);
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
            $lenght = 0;
            foreach ($route['params'] as $key => &$param) {
                if ($param !== '') {
                    $param = urldecode($param);
                    $lenght = ++$key;
                }
            }
            $route['params'] = array_splice($route['params'], 0, $lenght);
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
        return strcasecmp($a['url'], $b['url']) < 0;
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

    private static function encodeURL($str)
    {
        $str = str_replace(
            array('á', 'à', 'ä', 'â', 'ª', 'Á', 'À', 'Â', 'Ä'), 'a', $str
        );
        $str = str_replace(
            array('é', 'è', 'ë', 'ê', 'É', 'È', 'Ê', 'Ë'), 'e', $str
        );
        $str = str_replace(
            array('í', 'ì', 'ï', 'î', 'Í', 'Ì', 'Ï', 'Î'), 'i', $str
        );
        $str = str_replace(
            array('ó', 'ò', 'ö', 'ô', 'Ó', 'Ò', 'Ö', 'Ô'), 'o', $str
        );
        $str = str_replace(
            array('ú', 'ù', 'ü', 'û', 'Ú', 'Ù', 'Û', 'Ü'), 'u', $str
        );
        $str = str_replace(
            array(' ', 'ñ', 'Ñ', 'ç', 'Ç'), array('-', 'n', 'N', 'c', 'C'), $str
        );
        return urlencode(strtolower($str));
    }
}
