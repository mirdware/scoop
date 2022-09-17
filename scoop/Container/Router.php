<?php
namespace Scoop\Container;

class Router
{
    private $routes = array();
    private $current;

    public function __construct($routes)
    {
        foreach ($routes as $key => $route) {
            $this->load($route, $key);
        }
        uasort($this->routes, array($this, 'sortByURL'));
    }

    public function route($request)
    {
        $route = $this->getRoute($request->getURL());
        if ($route) {
            $method = strtolower($_SERVER['REQUEST_METHOD']);
            $controller = $this->getController($route['controller'], $method);
            if ($controller) {
                $this->intercept($request);
                $controllerReflection = new \ReflectionClass($controller);
                if (!$controllerReflection->hasMethod($method)) {
                    throw new \Scoop\Http\MethodNotAllowedException($controllerReflection->getName().' not implement '.$method.' method');
                }
                $method = $controllerReflection->getMethod($method);
                $numParams = count($route['params']);
                if (
                    $numParams >= $method->getNumberOfRequiredParameters() &&
                    $numParams <= $method->getNumberOfParameters()
                ) {
                    return $method->invokeArgs($controller, $route['params']);
                }
            }
        }
        throw new \Scoop\Http\NotFoundException();
    }

    public function intercept($request)
    {
        $matches = $this->filterProxy($request->getURL());
        foreach ($matches as $route) {
            $proxy = \Scoop\Context::inject($route['proxy']);
            $proxy->process($request);
        }
    }

    public function getURL($key, $params, $query)
    {
        $path = preg_split('/\{\w+\}/', $this->routes[$key]['url']);
        $url = array_shift($path);
        $count = count($path);
        if (count($params) > $count) {
            throw new \InvalidArgumentException('Unformed URL');
        }
        for ($i = 0; $i < $count; $i++) {
            if (isset($params[$i])) {
                $url .= self::encodeURL(trim($params[$i])).$path[$i];
            }
        }
        if (strrpos($url, '/') !== strlen($url)-1) {
            $url .= '/';
        }
        return ROOT.substr($url, 1).$this->formatQueryString($query);
    }

    public function formatQueryString($query)
    {
        if (!is_array($query)) return '';
        $queryString = '';
        foreach ($query AS $name => $value) {
            if ($value) {
                $queryString .= '&'.htmlentities($name, ENT_QUOTES | ENT_HTML5, 'UTF-8').'='.htmlentities($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }
        }
        return $queryString ? '?'.substr($queryString, 1) : '';
    }

    public function getCurrentRoute()
    {
        return $this->current;
    }

    private function getController($controller, $method)
    {
        if (is_array($controller)) {
            if (!isset($controller[$method])) {
                throw new \Scoop\Http\MethodNotAllowedException('There not controller for '.$method.' method');
            };
            $controller = $controller[$method];
        }
        if (!class_exists($controller)) {
            throw new \Scoop\Http\NotFoundException('Class '.$controller.' not found');
        }
        $baseController = '\Scoop\Controller';
        if (!is_subclass_of($controller, $baseController)) {
            throw new \UnexpectedValueException($controller.' not implement '.$baseController);
        }
        return \Scoop\Context::inject($controller);
    }

    private function getRoute($url)
    {
        $matches = $this->filterRoute($url);
        if ($matches) {
            $route = end($matches);
            $this->current = key($matches);
            array_shift($route['params']);
            $lenght = 0;
            foreach ($route['params'] as $key => $param) {
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
        foreach ($this->routes as $key => $route) {
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
        foreach ($this->routes as $route) {
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
            $routes = $route['routes'];
            if (is_string($routes)) {
                $routes = \Scoop\Context::getEnvironment()->loadLazily($routes);
                if (is_string($routes)) {
                    throw new \InvalidArgumentException('routes '.$routes.' not supported');
                }
            }
            foreach ($routes as $k => $r) {
                $this->load($r, $k, $route['url']);
            }
            unset($route['routes']);
        }
        $this->routes[$key] = $route;
    }

    private static function sortByURL($a, $b)
    {
        return strcasecmp($b['url'], $a['url']);
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
        return urlencode($str);
    }
}
