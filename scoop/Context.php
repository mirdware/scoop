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

    public static function connect($conf = null) {
        $bundle = 'db.default';
        if (is_string($conf)) {
            $bundle = $conf;
        }
        $config = self::$service->get('config')->get($bundle);
        if (is_array($conf)) {
            $config += $conf;
        }
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
