<?php
namespace Scoop;

class Context
{
    private static $connections = array();
    private static $configParameters = array();
    private static $loader;
    private static $service;
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
            self::$loader = new \Loader();
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

    /**
     * Obtiene la instancia del injector según las capacidades del servidor.
     * @return \Scoop\Container\Injector El injector apropiado para el servidor.
     * @deprecated
     */
    public static function getInjector()
    {
        return self::$injector;
    }

    public static function inject($id)
    {
        if (!self::$injector->has($id) && isset(self::$configParameters[$id])) {
            return self::$injector->create($id, self::$configParameters[$id]);
        }
        return self::$injector->get($id);
    }

    /**
     * Obtiene un servicio configurado previamente
     * @deprecated
     * @param string $service Nombre del servicio a buscar.
     * @return object Servicio hallado.
     * @throws \UnderflowException Si no se ha registrado ningún servicio arroja la excepción.
     */
    public static function getService($service)
    {
        if (!self::$service) {
            throw new \UnderflowException('No service '.$service.' registered');
        }
        return self::$service->get($service);
    }

    /**
     * Registra un servicio en el service manager.
     * @deprecated
     * @param string $key Nombre del servicio
     * @param string|object $callback Nombre de la clase del servicio
     *  o el objeto mismo que representa el servicio.
     * @param array<mixed> $params Parametros enviados al servicio.
     */
    public static function registerService($key, $callback, $params = array())
    {
        if (!self::$service) {
            self::$service = new \Scoop\Container\Service();
        }
        self::$service->register($key, $callback, $params);
    }

    private static function normalizeConnection($bundle, $options)
    {
        $config = self::$environment->getConfig('db.'.$bundle, array()) + $options;
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
        self::$configParameters['Scoop\Event\Bus'] = array(
            'providers' => self::$environment->getConfig('events', array())
        );
    }

    private static function configureInjector()
    {
        $injector = self::$environment->getConfig('injector', '\Scoop\Container\BasicInjector');
        $baseInjector = '\Scoop\Container\Injector';
        self::$injector = new $injector(self::$environment);
        if (!(self::$injector instanceof $baseInjector)) {
            throw new \UnexpectedValueException($injector.' not implement '.$baseInjector);
        }
    }

    private static function configureLogger()
    {
        $loggers = self::$environment->getConfig('loggers', array());
        $handlers = array();
        foreach ($loggers as $level => $value) {
            if (isset($value['handler'])) {
                $handler = $value['handler'];
                unset($value['handler']);
                $handlers[$level] = $handler;
                self::$configParameters[$handler] = $value;
            }
        }
        self::$configParameters['Scoop\Log\Logger'] = compact('handlers');
    }
}
