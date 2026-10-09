<?php

namespace Scoop\View;

abstract class Service
{
    private static $context = null;
    private static $injector = null;
    private static $services = array();

    public static function setUp(\Scoop\Bootstrap\Environment $context)
    {
        self::$context = $context;
        self::$injector = $context->getInjector();
    }

    public static function reset()
    {
        self::$context = null;
        self::$injector = null;
        self::$services = array();
    }

    public static function getContext()
    {
        return self::$context;
    }

    public static function takeSnapshot()
    {
        return self::$services;
    }

    public static function restore($services)
    {
        self::$services = $services;
    }

    public static function inject($name, $className)
    {
        self::$services[$name] = $className;
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
