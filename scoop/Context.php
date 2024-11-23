<?php

namespace Scoop;

class Context
{
    private static $connections = array();
    private static $loader;
    private static $injector;
    private static $environment;

    public static function load($configPath)
    {
        if (!isset(self::$loader)) {
            if (is_readable('vendor/autoload.php')) {
                self::$loader = require 'vendor/autoload.php';
            } else {
                require 'scoop/Bootstrap/Loader.php';
                self::$loader = new \Scoop\Bootstrap\Loader();
                $conf = json_decode(file_get_contents('composer.json'), true);
                $psr4 = $conf['autoload']['psr-4'];
                foreach ($psr4 as $key => $value) {
                    self::$loader->set($key, $value);
                }
                self::$loader->register(true);
            }
        }
        self::configureInjector(
            new \Scoop\Bootstrap\Environment($configPath)
        );
        self::inject('Scoop\Bootstrap\Configuration')->setUp();
    }

    public static function connect($bundle = 'default', $options = array())
    {
        $config = self::normalizeConnection($bundle, $options);
        $key = implode('', $config);
        if (!isset(self::$connections[$key])) {
            self::$connections[$key] = new Persistence\DBC(
                $config['database'],
                $config['user'],
                $config['password'],
                $config['host'],
                $config['port'],
                $config['driver']
            );
        }
        return self::$connections[$key];
    }

    public static function disconnect($bundle = 'default', $options = array())
    {
        $key = implode('', self::normalizeConnection($bundle, $options));
        unset(self::$connections[$key]);
    }

    public static function reset()
    {
        foreach (self::$connections as $connection) {
            $connection->rollBack();
        }
        self::configureInjector(
            self::inject('Scoop\Bootstrap\Environment')
        );
    }

    /**
     * @deprecated [7.3] No use
     */
    public static function getLoader()
    {
        return self::$loader;
    }

    /**
     * @deprecated [7.4] Inject \Scoop\Bootstrap\Environment
     * @return \Scoop\Bootstrap\Environment
     */
    public static function getEnvironment()
    {
        return self::$environment;
    }

    public static function inject($id)
    {
        return self::$injector->get($id);
    }

    private static function normalizeConnection($bundle, $options)
    {
        $config = self::$environment->getConfig('db.' . $bundle, array()) + $options;
        $requireds = array('database', 'user');
        foreach ($requireds as $required) {
            if (!isset($config[$required])) {
                throw new \OutOfBoundsException('Property ' . $required .
                ' not found in database configuration');
            }
        }
        return array_merge(array(
            'password' => '',
            'host' => '127.0.0.1',
            'port' => null,
            'driver' => 'pgsql'
        ), $config);
    }

    private static function configureInjector($environment)
    {
        $injector = $environment->getConfig('injector', '\Scoop\Container\BasicInjector');
        $baseInjector = '\Scoop\Container\Injector';
        self::$environment = $environment;
        self::$injector = new $injector($environment);
        if (!(self::$injector instanceof $baseInjector)) {
            throw new \UnexpectedValueException("$injector not implement $baseInjector");
        }
    }
}
