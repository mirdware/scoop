<?php
namespace Scoop\IoC;

class Injector
{
    private static $rules = array();
    private static $instances = array();

    public function create($className, $args = array())
    {
        $class = new \ReflectionClass($className);
        return $this->instantiate($class, $args);
    }

    public function bind($interfaceName, $className)
    {
        self::$rules[$interfaceName] = $className;
    }

    public function getInstance($className)
    {
        if (!isset(self::$instances[$className])) {
            $class = new \ReflectionClass($className);
            if ($class->isInterface()) {
                $className = self::$rules[$className];
            }
            self::$instances[$className] = $this->create($className);
        }
        return self::$instances[$className];
    }

    private function instantiate(\ReflectionClass &$class, $args = array())
    {
        $constructor = $class->getConstructor();
        if ($constructor) {
            $args = array_merge($this->getParams($constructor), $args);
            return $class->newInstanceArgs($args);
        }
        return $class->newInstanceWithoutConstructor();
    }

    private function getParams(\ReflectionMethod &$method)
    {
        $params = $method->getParameters();
        $args = array();
        foreach ($params as &$param) {
            $class = $param->getClass();
            if ($class) {
                $args[] = $this->getInstance($class->getName());
            }
        }
        return $args;
    }
}
