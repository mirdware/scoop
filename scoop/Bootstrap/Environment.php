<?php
namespace Scoop\Bootstrap;

class Environment
{
    private static $sessionInit = false;
    private $router;
    private $config;

    public function __construct($configPath)
    {
        if (!self::$sessionInit) {
            self::$sessionInit = session_start();
        }
        $this->config = array(
            'base' => require $configPath.'.php',
            'data' => array()
        );
        $protocol = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443 ? 'https:' : 'http:';
        define('ROOT', $protocol.'//'.$_SERVER['HTTP_HOST'].rtrim(dirname($_SERVER['PHP_SELF']), '/\\').'/');
    }

    public function getConfig($name, $default = null)
    {
        if (isset($this->config['data'][$name])) {
            return $this->config['data'][$name];
        }
        $data = explode('.', $name);
        $res = $this->config['base'];
        foreach ($data as $key) {
            if (!isset($res[$key])) return $default;
            if (is_string($res[$key])) {
                $res[$key] = $this->loadLazily($res[$key]);
            }
            $res = $res[$key];
        }
        $this->config['data'][$name] = $res;
        return $res;
    }

    public function loadLazily($path)
    {
        $index = strpos($path, ':') + 1;
        if ($index !== -1) {
            $method = substr($path, 0, $index);
            $url = substr($path, $index);
            if ($method === 'json:') {
                return json_decode(file_get_contents($url . '.json'), true);
            }
            if ($method === 'import:') {
                return require $url . '.php';
            }
        }
        return $path;
    }

    public function route($request)
    {
        $this->configure($request);
        \Scoop\Controller::setRequest($request);
        \Scoop\View::setRequest($request);
        return $this->router->route($request);
    }

    public function getURL($args)
    {
        $query = array_pop($args);
        if ($query !== null && !is_array($query)) {
            array_push($args, $query);
            $query = null;
        }
        if (empty($args)) {
            $currentPath = '//'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
            if ($query) {
                return $this->mergeQuery($currentPath, $query);
            }
            return $currentPath;
        }
        return $this->router->getURL(array_shift($args), $args, $query);
    }

    public function isCurrentRoute($route)
    {
        return $this->router->getCurrentRoute() === $route;
    }

    protected function configure($request) {
        \Scoop\Validator::setMessages((Array) $this->getConfig('messages.error'));
        \Scoop\Validator::addRule((Array) $this->getConfig('validators'));
        $this->bind((Array) $this->getConfig('providers'));
        $this->registerComponents((Array) $this->getConfig('components'));
        $this->registerServices(array('config' => $this, 'request' => $request));
        $this->router = new \Scoop\IoC\Router((Array) $this->getConfig('routes'));
        return $this;
    }

    protected function bind($interfaces)
    {
        $injector = \Scoop\Context::getInjector();
        foreach ($interfaces as $interface => $class) {
            $injector->bind($interface, $class);
        }
        return $this;
    }

    protected function registerServices($services)
    {
        foreach ($services as $name => $service) {
            is_array($service) ?
                \Scoop\Context::registerService($name, array_shift($service), $service) :
                \Scoop\Context::registerService($name, $service);
        }
        return $this;
    }

    protected function registerComponents($components)
    {
        foreach ($components as $name => $component) {
            \Scoop\View::registerComponent($name, $component);
        }
        return $this;
    }

    private function getQuery($params)
    {
        $query = array();
        $params = explode('&', $params);
        foreach ($params AS $param) {
            $param = explode('=', $param);
            $query[$param[0]] = $param[1];
        }
        return $query;
    }

    private function mergeQuery($url, $query)
    {
        $url = explode('?', $url);
        if (isset($url[1])) {
            $query += $this->getQuery($url[1]);
        }
        return $url[0].$this->router->formatQueryString($query);
    }
}
