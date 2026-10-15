<?php

namespace Scoop;

class Context
{
    private static $loaders = array(
        'import' => 'Scoop\Bootstrap\Loader\Importer',
        'typeof' => 'Scoop\Bootstrap\Loader\TypeMapper',
        'instanceof' => 'Scoop\Bootstrap\Loader\TypeInstantiator',
        'json' => 'Scoop\Bootstrap\Loader\Factory\JsonParser:create'
    );
    private $injector;
    private $config;
    private $storagePath;

    public function __construct($context)
    {
        if (!defined('DEBUG_MODE')) {
            define('DEBUG_MODE', filter_var(ini_get('display_errors'), FILTER_VALIDATE_BOOLEAN));
        }
        $this->config = $context['map'] . '.php';
        $this->storagePath = $context['storage'];
        $this->injector = $this->instantiateInjector();
    }

    public function getConfig($name, $default = null)
    {
        if (is_string($this->config)) {
            $this->config = array(
                'base' => require $this->config,
                'data' => array()
            );
            if (!isset($this->config['base']['providers']) || !is_array($this->config['base']['providers'])) {
                throw new \UnexpectedValueException('it is not possible to perform lazy loading on providers');
            }
            self::$loaders += $this->getConfig('loaders', array());
        }
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
                $res[$key] = $this->loadLazily($res[$key]);
            }
            $res = $res[$key];
        }
        $this->config['data'][$name] = $res;
        return $res;
    }

    public function getStoragePath($path)
    {
        $path = trim($this->storagePath, '/') . '/' . trim($path, '/') . '/';
        if (!is_dir($path) && !@mkdir($path, 0755, true) && !is_dir($path)) {
            throw new \RuntimeException("Cannot create storage directory: $path");
        }
        return $path;
    }

    public function getInjector()
    {
        return $this->injector;
    }

    public function loadLazily($path)
    {
        $index = strpos($path, ':');
        if ($index !== false) {
            $method = substr($path, 0, $index);
            if (isset(self::$loaders[$method])) {
                $url = substr($path, $index + 1);
                $loader = $this->injector->get(self::$loaders[$method]);
                return $loader->load($url);
            }
        }
        return $path;
    }

    private function instantiateInjector()
    {
        $injector = $this->getConfig('injector', '\Scoop\Container\Injector\Memory');
        $baseInjector = '\Scoop\Container\Injector';
        $injector = new $injector($this);
        if (!($injector instanceof $baseInjector)) {
            throw new \UnexpectedValueException("$injector not implement $baseInjector");
        }
        return $injector;
    }
}
