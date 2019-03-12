<?php
namespace Scoop\IoC;

class BasicInjector extends Injector
{
    private static $rules = array();
    private static $instances = array();

    public function bind($interfaceName, $className)
    {
        $interfaceName = self::formatClassName($interfaceName);
        $class = new \ReflectionClass($className);
        if (!$class->isSubclassOf($interfaceName)) {
            throw new \UnexpectedValueException('class '.$class->getName().' can not binding to '.$interfaceName);
        }
        self::$rules[$interfaceName] = $class->getName();
    }

    public function getInstance($className)
    {
        $className = self::formatClassName($className);
        if (!isset(self::$instances[$className])) {
            if (isset(self::$rules[$className])) {
                return self::getInstance(self::$rules[$className]);
            }
            self::$instances[$className] = $this->create($className);
        }
        return self::$instances[$className];
    }
}
