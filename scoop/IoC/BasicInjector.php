<?php
namespace Scoop\IoC;

class BasicInjector extends Injector
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
}
