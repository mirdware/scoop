<?php
namespace Scoop;

class Context
{
    private static $connections = array();
    private static $loader;
    private static $service;
    private static $injector;

    public static function load()
    {
        if (!isset(self::$loader)) {
            if (is_readable('vendor/autoload.php')) {
                self::$loader = require 'vendor/autoload.php';
            } else {
                require 'scoop/Bootstrap/Loader.php';
                self::$loader = new Loader();
                $conf = json_decode(file_get_contents('composer.json'), true);
                $psr4 = $conf['autoload']['psr-4'];
                foreach ($psr4 as $key => $value) {
                    self::$loader->setPsr4($key, $value);
                }
                self::$loader->register(true);
            }
        }
        return self::$loader;
    }

    public static function connect($bundle = null)
    {
        $config = self::getDBConfig($bundle);
        $config = self::normalizeDBConfig($config);
        $key = implode('', $config);
        if (!isset(self::$connections[$key])) {
            self::$connections[$key] = new Storage\DBC(
                $config['database'],
                $config['user'],
                $config['password'],
                $config['host'],
                $config['driver']
            );
        }
        return self::$connections[$key];
    }

    public static function getInjector()
    {
        if (!self::$injector) {
            self::$injector = new \Scoop\IoC\BasicInjector();
        }
        return self::$injector;
    }

    public static function getService($service)
    {
        if (!self::$service) {
            throw new \UnderflowException('No service registered');
        }
        return self::$service->get($service);
    }

    public static function registerService($key, $callback, $params = array())
    {
        if (!self::$service) {
            self::$service = new \Scoop\IoC\Service();
        }
        self::$service->register($key, $callback, $params);
    }

    private static function getDBConfig($bundle)
    {
        $serviceConfig = self::getService('config');
        if (is_string($bundle)) return $serviceConfig->get('db'.$bundle);
        $config = $serviceConfig->get('db.default');
        if (is_array($bundle)) {
            $config += $bundle;
        }
        return $config;
    }

    private static function normalizeDBConfig($config)
    {
        $requireds = array('database', 'user');
        foreach ($requireds as $required) {
            if (!isset($config[$required])) {
                throw new \OutOfBoundsException('Property '.$required.
                ' not found in database configuration');
            }
        }
        return array_merge(array(
            'password' => '',
            'host' => '127.0.0.1',
            'driver' => 'pgsql'
        ), $config);
    }
}
