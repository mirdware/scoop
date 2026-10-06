<?php

namespace Scoop\View;

abstract class Service
{
    private static $context;
    private static $injector;
    private static $services = array();

    public static function setup(\Scoop\Bootstrap\Environment $context)
    {
        self::$context = $context;
        self::$injector = $context->getInjector();
    }

    public static function clean()
    {
        self::$services = array();
        unset(self::$context, self::$injector);
    }

    public static function getContext()
    {
        return self::$context;
    }

    public static function inject($name, $className)
    {
        $previous = isset(self::$services[$name]) ? self::$services[$name] : null;
        self::$services[$name] = $className;
        return $previous;
    }

    public static function get($name)
    {
        if (!isset(self::$services[$name])) {
            throw new \UnderflowException("No service $name registered");
        }
        $service = self::$services[$name];
        if (is_string($service)) {
            $service = self::$injector->get($service);
        }
        return $service;
    }
}
