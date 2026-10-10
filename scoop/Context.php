<?php

namespace Scoop;

class Context extends Bootstrap\Environment
{
    private static $connections = array();
    private static $context;
    private static $loader;
    private static $app;

    /**
     * @deprecated
     * @since 0.8.5
     * @see Application instance
     */
    public static function load($configPath, $options = array())
    {
        if ($configPath instanceof \Scoop\Bootstrap\Application) {
            self::$app = $configPath;
        } else {
            $options['config'] = $configPath;
            self::$context = $options;
            if (is_readable('vendor/autoload.php')) {
                self::$loader = require 'vendor/autoload.php';
            } else {
                require 'scoop/Bootstrap/Loader.php';
                require 'scoop/Bootstrap/Loader/JsonParser.php';
                self::$loader = new \Scoop\Bootstrap\Loader();
                $storagePath = isset($options['storage']) ? $options['storage'] : 'app/storage';
                $path = trim($storagePath, '/') . '/cache/json/';
                if (!is_dir($path)) {
                    mkdir($path, 0755, true);
                }
                $jsonLoader = new \Scoop\Bootstrap\Loader\JsonParser($path);
                $conf = $jsonLoader->load('composer');
                if (isset($conf['autoload']['psr-4'])) {
                    foreach ($conf['autoload']['psr-4'] as $key => $value) {
                        self::$loader->set($key, $value);
                    }
                }
                self::$loader->register(true);
            }
        }
    }

    /**
     * @deprecated
     */
    public static function get()
    {
        return is_array(self::$context) ? self::$context : array();
    }

    /**
     * @deprecated
     */
    public static function getLoader()
    {
        return self::$loader;
    }

    /**
     * @deprecated
     * @since 0.8.5
     * @see Connection inject
     */
    public static function connect($bundle = 'default', $options = array())
    {
        $config = self::normalizeConnection($bundle, $options);
        $key = implode('', $config);
        if (!isset(self::$connections[$key])) {
            self::$connections[$key] = new Persistence\Connection(
                self::inject('\Scoop\Event\Dispatcher'),
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

    /**
     * @deprecated
     * @since 0.8.5
     * @see Connection::__destruct
     */
    public static function disconnect($bundle = 'default', $options = array())
    {
        $key = implode('', self::normalizeConnection($bundle, $options));
        unset(self::$connections[$key]);
    }

    /**
     * @deprecated
     * @since 0.8.5
     * @see Connection::rollback
     */
    public static function reset()
    {
        foreach (self::$connections as $connection) {
            $connection->rollBack();
        }
    }

    /**
     * @deprecated
     * @since 0.8.5
     * @see Application::inject
     * @see Environment::inject
     */
    public static function inject($id)
    {
        if (!isset(self::$app)) {
            self::$app = new \Scoop\Bootstrap\Application();
        }
        return self::$app->inject($id);
    }

    private static function normalizeConnection($bundle, $options)
    {
        $config = self::inject('Scoop\Bootstrap\Environment')->getConfig('db.' . $bundle, array()) + $options;
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
}
