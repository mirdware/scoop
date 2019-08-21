<?php
namespace Scoop;

class Context
{
    private static $connections = array();
    private static $loader;
    private static $service;
    private static $injector;

    /**
     * Ejecuta la carga automatica de clases mediante Composer o con una clase propia.
     * @return vendor|\Scoop\Bootstrap\Loader El cargador usado para ejecutar la carga.
     */
    public static function load()
    {
        if (!isset(self::$loader)) {
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
        }
        return self::$loader;
    }

    /**
     * Conecta a una base de datos
     * @param string|array<mixed> $bundle El nombre del configuration bundle (EJ: default)
     *  o un array con los datos de configuración necesaria para la creación (database, user).
     * @return \Scoop\Storage\DBC La conexión establecida con el servidor.
     */
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

    /**
     * Obtiene la instancia del injector según las capacidades del servidor.
     * @return \Scoop\IoC\Injector El injector apropiado para el servidor.
     */
    public static function getInjector()
    {
        if (!self::$injector) {
            self::$injector = new \Scoop\IoC\BasicInjector();
        }
        return self::$injector;
    }

    /**
     * Obtiene un servicio configurado previamente
     * @param string $service Nombre del servicio a buscar.
     * @return object Servicio hallado.
     * @throws \UnderflowException Si no se ha registrado ningún servicio arroja la excepción.
     */
    public static function getService($service)
    {
        if (!self::$service) {
            throw new \UnderflowException('No service registered');
        }
        return self::$service->get($service);
    }

    /**
     * Registra un servicio en el service manager.
     * @param string $key Nombre del servicio
     * @param string|object $callback Nombre de la clase del servicio 
     *  o el objeto mismo que representa el servicio.
     * @param array<mixed> $params Parametros enviados al servicio.
     */
    public static function registerService($key, $callback, $params = array())
    {
        if (!self::$service) {
            self::$service = new \Scoop\IoC\Service();
        }
        self::$service->register($key, $callback, $params);
    }

    /**
     * Obtiene la configuración de la base de datos establecida en /app/config::db
     * Tambien puede mezclar los datos del array con los de la configuración por defecto.
     * @param string|array<array<string>> $bundle El nombre del configuration bundle (EJ: default)
     *  o un array con los datos de configuración necesaria para la creación (database, user).
     * @return array<array<string>> Array de configuración.
     */
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

    /**
     * Agrega los datos por defecto para ejecutar la configuración.
     * @param array<array<string>> $config Array de configuración.
     * @return array<array<string>> Configuración normalizada.
     */
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
