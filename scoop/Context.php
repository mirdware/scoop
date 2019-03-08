<?php
namespace Scoop;

class Context
{
    private static $connections = array();
    private static $loader;
    private static $service;
    private static $injector;
    private static $request;

    public static function load() {
        if (!isset(self::$loader)) {
            if (is_readable('vendor/autoload.php')) {
                self::$loader = require 'vendor/autoload.php';
            } else {
                require 'scoop/Bootstrap/Loader.php';
                self::$loader = new Loader();
                $conf = json_decode(file_get_contents('composer.json'), true);
                $psr4 = $conf['autoload']['psr-4'];
                foreach ($psr4 as $key => &$value) {
                    self::$loader->setPsr4($key, $value);
                }
                self::$loader->register(true);
            }
        }
        return self::$loader;
    }

    public static function connect($bundle = null) {
        if (is_string($bundle)) {
            $config = self::getService('config')->get('db'.$bundle);
        } else {
            $config = self::getService('config')->get('db.default');
            if (is_array($bundle)) $config += $bundle;
        }
        $requireds = array('database', 'user');
        foreach ($requireds as &$required) {
            if (!isset($config[$required])) {
                throw new \Exception('Property '.$required.' not found in database configuration');
            }
        }
        $key = implode('', $config);
        if (!isset(self::$connections[$key])) {
            self::$connections[$key] = new Storage\DBC(
                $config['database'],
                $config['user'],
                isset($config['password']) ? $config['password'] : '',
                isset($config['host']) ? $config['host'] : '127.0.0.1',
                isset($config['driver']) ? $config['driver'] : 'pgsql'
            );
        }
        return self::$connections[$key];
    }

    public static function getInjector() {
        if (!self::$injector) {
            self::$injector = new \Scoop\IoC\Injector();
        }
        return self::$injector;
    }

    public static function getRequest() {
        if (!self::$request) {
            self::$request = new \Scoop\Http\Request();
        }
        return self::$request;
    }

    public static function getService($service) {
        if (!self::$service) {
            self::$service = new \Scoop\IoC\Service();
        }
        return self::$service->get($service);
    }

    public static function registerService($key, $callback, $params = array()) {
        if (!self::$service) {
            self::$service = new \Scoop\IoC\Service();
        }
        self::$service->register($key, $callback, $params);
    }
}
