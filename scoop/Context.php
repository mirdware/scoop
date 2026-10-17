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

    public function __construct(\Scoop\Bootstrap\Lifecycle $lifecycle, $map)
    {
        if (!defined('DEBUG_MODE')) {
            define('DEBUG_MODE', filter_var(ini_get('display_errors'), FILTER_VALIDATE_BOOLEAN));
        }
        $this->config = $map['map'] . '.php';
        $this->storagePath = $map['storage'];
        $this->injector = $this->instantiateInjector($lifecycle);
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
        $path = rtrim($this->storagePath, '/') . '/' . trim($path, '/') . '/';
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

    private function instantiateInjector($lifecycle)
    {
        $injector = $this->getConfig('injector', '\Scoop\Container\Injector\Memory');
        $baseInjector = '\Scoop\Container\Injector';
        $injector = new $injector($this, $lifecycle);
        if (!($injector instanceof $baseInjector)) {
            throw new \UnexpectedValueException("$injector not implement $baseInjector");
        }
        return $injector;
    }
}
