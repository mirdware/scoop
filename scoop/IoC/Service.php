<?php
namespace Scoop\IoC;

abstract class Service
{
    private static $services = array();

    public static function register($key, $callback, $params = array())
    {
        if (is_string($callback)) {
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
        return call_user_func_array($serv['callback'], $serv['params']);
    }
}
