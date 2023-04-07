<?php

namespace Scoop;

class Context
{
    private static $connections = array();
    private static $configParameters = array();
    private static $loader;
    private static $injector;
    private static $environment;

    public static function load($configPath)
    {
        if (isset(self::$loader)) {
            throw new \Exception('Context loaded');
        }
        if (is_readable('vendor/autoload.php')) {
            self::$loader = require 'vendor/autoload.php';
        } else {
            require 'scoop/Bootstrap/Loader.php';
            self::$loader = new \Scoop\Bootstrap\Loader();
            $conf = json_decode(file_get_contents('composer.json'), true);
            $psr4 = $conf['autoload']['psr-4'];
            foreach ($psr4 as $key => $value) {
                self::$loader->setPsr4($key, $value);
            }
            self::$loader->register(true);
        }
        self::$environment = new \Scoop\Bootstrap\Environment($configPath);
        self::configure();
    }

    public static function connect($bundle = 'default', $options = array())
    {
        $config = self::normalizeConnection($bundle, $options);
        $key = implode('', $config);
        if (!isset(self::$connections[$key])) {
            self::$connections[$key] = new Storage\DBC(
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
        if (!self::$injector->has($id) && isset(self::$configParameters[$id])) {
            return self::$injector->create($id, self::$configParameters[$id]);
        }
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
        self::configureLogger();
        \Scoop\Validator::setMessages(self::$environment->getConfig('messages.error', array()));
        \Scoop\Validator::addRules(self::$environment->getConfig('validators', array()));
        \Scoop\View::registerComponents(self::$environment->getConfig('components', array()));
        self::$configParameters['Scoop\Storage\Crypt'] = self::$environment->getConfig('crypt', array());
        self::$configParameters['Scoop\Event\Bus'] = array(
            'providers' => self::$environment->getConfig('events', array())
        );
        self::$configParameters['Scoop\Storage\Entity\Manager'] = array(
            'map' => self::$environment->getConfig('entities', array())
        );
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

    private static function configureLogger()
    {
        $logHandlers = self::$environment->getConfig('log', array());
        $handlers = array();
        foreach ($logHandlers as $level => $params) {
            if (!isset($params['handler'])) {
                throw new \UnexpectedValueException('Handler not configured for ' . $level . ' level');
            }
            $handler = $params['handler'] . ':' . $level;
            unset($params['handler']);
            $handlers[$level] = $handler;
            self::$configParameters[$handler] = $params;
        }
        self::$configParameters['Scoop\Log\Logger'] = compact('handlers');
    }
}
