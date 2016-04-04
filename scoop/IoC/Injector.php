<?php
namespace Scoop\IoC;

abstract class Injector
{
    private static $rules = array();
    private static $instances = array();

    public static function create($className, $args = array())
    {
        return self::instantiate(new \ReflectionClass($className), $args);
    }

    public static function bind($interfaceName, $className)
    {
        self::$rules[$interfaceName] = $className;
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
                $className = $class->getName();
                if ($class->isInterface()) {
                    $class = self::replaceRule($className);
                }
                if (!isset(self::$instances[$className])) {
                    self::$instances[$className] = self::instantiate($class);
                }
                $args[] = self::$instances[$className];
            }
        }
        return $args;
    }

    private static function replaceRule($className)
    {
        return new \ReflectionClass(self::$rules[$className]);
    }
}
