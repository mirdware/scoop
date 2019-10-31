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
        define('ROOT', '//'.$_SERVER['HTTP_HOST'].rtrim(dirname($_SERVER['PHP_SELF']), '/\\').'/');
    }

    public function get($name)
    {
        if (isset($this->config['data'][$name])) {
            return $this->config['data'][$name];
        }
        $data = explode('.', $name);
        $res = $this->config['base'];
        foreach ($data as $key) {
            if (!isset($res[$key])) return null;
            if (is_string($res[$key])) {
                $res[$key] = $this->load($res[$key]);
            }
            $res = $res[$key];
        }
        $this->config['data'][$name] = $res;
        return $res;
    }

    public function load($value)
    {
        $index = strpos($value, ':') + 1;
        if ($index !== -1) {
            $method = substr($value, 0, $index);
            $url = substr($value, $index);
            if ($method === 'json:') {
                return json_decode(file_get_contents($url . '.json'), true);
            }
            if ($method === 'import:') {
                return require $url . '.php';
            }
        }
        return $value;
    }

    public function route($url)
    {
        $this->configure();
        return $this->router->route($url);
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

    protected function configure() {
        \Scoop\Validator::setMessages((Array) $this->get('messages.error'));
        \Scoop\Validator::addRule((Array) $this->get('validators'));
        $this->bind((Array) $this->get('providers'));
        $this->registerComponents((Array) $this->get('components'));
        $services = (Array) $this->get('services');
        $services += array('config' => $this, 'request' => new \Scoop\Http\Request());
        $this->registerServices($services);
        $this->router = new \Scoop\IoC\Router((Array) $this->get('routes'));
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
