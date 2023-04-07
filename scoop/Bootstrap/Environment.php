<?php

namespace Scoop\Bootstrap;

class Environment
{
    private static $sessionInit = false;
    private static $loaders = array(
        'import' => '\Scoop\Bootstrap\Loader\Import',
        'json' => '\Scoop\Bootstrap\Loader\Json'
    );
    private static $version;
    private $router;
    private $config;

    public function __construct($configPath)
    {
        if (!self::$sessionInit) {
            self::$sessionInit = session_start();
        }
        $this->config = array(
            'base' => require $configPath . '.php',
            'data' => array()
        );
        if (isset($_SERVER['HTTP_HOST'])) {
            $http = !empty($_SERVER['HTTPS']) &&
                $_SERVER['HTTPS'] !== 'off' ||
                $_SERVER['SERVER_PORT'] == 443 ? 'https:' : 'http:';
            define('ROOT', $http . '//' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . '/');
        }
        self::$loaders += $this->getConfig('loaders', array());
    }

    public function getConfig($name, $default = null)
    {
        if (isset($this->config['data'][$name])) {
            return $this->config['data'][$name];
        }
        $data = explode('.', $name);
        $res = $this->config['base'];
        foreach ($data as $key) {
            if (!isset($res[$key])) {
                return $default;
            }
            if (is_string($res[$key])) {
                if ($key === 'providers') {
                    throw new \UnexpectedValueException('it is not possible to perform lazy loading on providers');
                }
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
                $loader = \Scoop\Context::inject(self::$loaders[$method]);
                return $loader->load($url);
            }
        }
        return $path;
    }

    public function route($request)
    {
        $this->router = new \Scoop\Container\Router($this->getConfig('routes', array()));
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
            $currentPath = '//' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            if (!$query) {
                $query = array();
            }
            return $this->mergeQuery($currentPath, $query);
        }
        return $this->router->getURL(array_shift($args), $args, $query);
    }

    public function getCurrentRoute()
    {
        return $this->router->getCurrentRoute();
    }

    public function getVersion()
    {
        if (!self::$version) {
            $index = file_get_contents('index.php');
            preg_match_all('# @version\s+(.*?)\n#s', $index, $annotations);
            self::$version = $annotations[1][0];
        }
        return self::$version;
    }

    private function getQuery($params)
    {
        $query = array();
        $params = explode('&', $params);
        foreach ($params as $param) {
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
        return $url[0] . $this->router->formatQueryString($query);
    }
}
