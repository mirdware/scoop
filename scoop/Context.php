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
        self::$environment = new \Scoop\Bootstrap\Environment($configPath);
        self::configure();
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
        self::configureInjector();
    }

    /**
     * @deprecated [7.3] No use
     */
    public static function getLoader()
    {
        return self::$loader;
    }

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

    private static function configure()
    {
        self::configureInjector();
        $lang = self::$environment->getConfig('language', 'es');
        \Scoop\Validator::setMessages(self::$environment->getConfig('messages.' . $lang . '.fail', array()));
        \Scoop\View::registerComponents(self::$environment->getConfig('components', array()));
    }

    private static function configureInjector()
    {
        $injector = self::$environment->getConfig('injector', '\Scoop\Container\BasicInjector');
        $baseInjector = '\Scoop\Container\Injector';
        self::$injector = new $injector(self::$environment);
        if (!(self::$injector instanceof $baseInjector)) {
            throw new \UnexpectedValueException($injector . ' not implement ' . $baseInjector);
        }
    }
}
