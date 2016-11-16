<?php
namespace Scoop\IoC;

abstract class Injector
{
    private static $rules = array();
    private static $instances = array();

    public static function create($className, $args = array())
    {
        $class = new \ReflectionClass($className);
        return self::instantiate($class, $args);
    }

    public static function bind($interfaceName, $className)
    {
        self::$rules[$interfaceName] = $className;
    }

    public static function getInstance($className)
    {
        if (!isset(self::$instances[$className])) {
            $class = new \ReflectionClass($className);
            if ($class->isInterface()) {
                $className = self::$rules[$className];
            }
            self::$instances[$className] = self::create($className);
        }
        return self::$instances[$className];
    }

    private static function instantiate(\ReflectionClass &$class, $args = array())
    {
        $constructor = $class->getConstructor();
        if ($constructor) {
            $args = array_merge(self::getParams($constructor), $args);
            return $class->newInstanceArgs($args);
        }
        return $class->newInstanceWithoutConstructor();
    }

    private static function getParams(\ReflectionMethod &$method)
    {
        $params = $method->getParameters();
        $args = array();
        foreach ($params as &$param) {
            $class = $param->getClass();
            if ($class) {
                $args[] = self::getInstance($class->getName());
            }
        }
        return $args;
    }
}
