<?php
namespace Scoop\View;

abstract class Service
{
    private static $services = array();

    public static function inject($name, $className)
    {
        self::$services[$name] = $className;
    }

    public static function get($name)
    {
        if (!isset(self::$services[$name])) {
            throw new \UnderflowException('No service '.$name.' registered');
        }
        $service = self::$services[$name];
        if (is_string($service)) {
            $service = \Scoop\Context::getInjector()->getInstance($service);
        }
        return $service;
    }
}
