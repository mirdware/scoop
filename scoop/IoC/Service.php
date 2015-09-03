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
        $search = array_map(array('\Scoop\IoC\Service', 'getSearch'),$serviceNames);
        $replace = array_map(array('\Scoop\IoC\Service', 'getReplace'),$serviceNames);
        $line = str_replace($search, $replace, $line);
    }

    private static function getSearch($serviceName)
    {
        return $serviceName.'->';
    }

    private static function getReplace($serviceName)
    {
        return '\Scoop\IoC\Service::getInstance(\''.$serviceName.'\')->';
    }
}
