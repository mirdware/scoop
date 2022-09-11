<?php
namespace Scoop\Bootstrap;

class Environment
{
    private static $sessionInit = false;
    private static $loaders = array(
        'import' => '\Scoop\Bootstrap\Loader\Import',
        'json' => '\Scoop\Bootstrap\Loader\Json'
    );
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
        if (isset($_SERVER['HTTP_HOST'])) {
            $protocol = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443 ? 'https:' : 'http:';
            define('ROOT', $protocol.'//'.$_SERVER['HTTP_HOST'].rtrim(dirname($_SERVER['PHP_SELF']), '/\\').'/');
        }
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
        $index = strpos($path, ':');
        if ($index !== -1) {
            $method = substr($path, 0, $index);
            if (isset(self::$loaders[$method])) {
                $url = substr($path, $index + 1);
                $loader = self::$loaders[$method];
                if (is_string($loader)) {
                    $loader = new $loader();
                }
                return $loader->load($url);
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
            if (!$query) $query = array();
            return $this->mergeQuery($currentPath, $query);
        }
        return $this->router->getURL(array_shift($args), $args, $query);
    }

    public function getCurrentRoute()
    {
        return $this->router->getCurrentRoute();
    }

    protected function configure($request) {
        $loaders = (Array) $this->getConfig('loaders');
        foreach ($loaders as $name => $className) {
            self::$loaders[strtolower($name)] = $className;
        }
        \Scoop\Validator::setMessages((Array) $this->getConfig('messages.error'));
        \Scoop\Validator::addRules((Array) $this->getConfig('validators'));
        \Scoop\View::registerComponents((Array) $this->getConfig('components'));
        $this->registerServices(array('config' => $this, 'request' => $request));
        $this->router = new \Scoop\Container\Router((Array) $this->getConfig('routes'));
        return $this;
    }

    /**
     * @deprecated
     */
    protected function registerServices($services)
    {
        foreach ($services as $name => $service) {
            is_array($service) ?
                \Scoop\Context::registerService($name, array_shift($service), $service) :
                \Scoop\Context::registerService($name, $service);
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
