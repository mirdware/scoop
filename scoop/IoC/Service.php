<?php
namespace Scoop\IoC;

abstract class Service
{
    private static $services = array();

    public static function register($key, $callback, $params = array())
    {
        if (!is_callable($callback)) {
            if (!is_string($callback)) {
                self::$services[$key]['instance'] = $callback;
                return;
            }
            $params = array($callback, $params);
            $callback = array('\Scoop\IoC\Injector', 'create');
        }

        self::$services[$key] = array(
            'callback' => $callback,
            'params' => $params
        );
    }

    public static function getInstance($key)
    {
        $serv = &self::$services[$key];
        if (!isset($serv['instance'])) {
            $serv['instance'] = call_user_func_array($serv['callback'], $serv['params']);
        }
        return $serv['instance'];
    }

    public static function create($key)
    {
        $serv = &self::$services[$key];
        if (!isset($serv['callback'])) {
            throw new Exception('Service use unsupported');
        }
        return call_user_func_array($serv['callback'], $serv['params']);
    }

    public static function compileView(&$line)
    {
        $serviceNames = array_keys(self::$services);
        $serviceFormats = array_map(array('\Scoop\IoC\Service', 'format'),$serviceNames);
        $line = str_replace($serviceNames, $serviceFormats, $line);
    }

    private static function format($serviceName)
    {
        return '\Scoop\IoC\Service::getInstance(\''.$serviceName.'\')';
    }
}
