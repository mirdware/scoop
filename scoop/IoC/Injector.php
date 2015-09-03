<?php
namespace Scoop\IoC;

abstract class Injector
{
    private static $rules = array();

    public static function create($className, $args = array())
    {
        return self::instantiate(new \ReflectionClass($className), $args);
    }

    public static function bind($interface, $class)
    {
        self::$rules[$interface] = $class;
    }

    private static function getParams(\ReflectionMethod &$method)
    {
        $params = $method->getParameters();
        $args = array();

        foreach ($params as $param) {
            $class = $param->getClass();
            if ($class) {
                if ($class->isInterface()) {
                    $class = self::replaceRule($class);
                }
                $args[] = self::instantiate($class);
            }
        }
        return $args;
    }

    private static function replaceRule(&$class)
    {
        return new \ReflectionClass(self::$rules[$class->getName()]);
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
}
