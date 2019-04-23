<?php
namespace Scoop\IoC;

class BasicInjector extends Injector
{
    private static $rules = array();
    private static $instances = array();

    public function bind($interfaceName, $className)
    {
        $interfaceName = self::formatClassName($interfaceName);
        $className = self::formatClassName($className);
        if (!is_subclass_of($className, $interfaceName)) {
            throw new \UnexpectedValueException('class '.$className.' can not binding to '.$interfaceName);
        }
        self::$rules[$interfaceName] = $className;
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
