<?php
namespace Scoop\IoC;

class BasicInjector extends Injector
{
    private $instances = array();

    public function getInstance($className)
    {
        $className = self::formatClassName($className);
        if (!isset(self::$instances[$className])) {
            if (isset($this->rules[$className])) {
                return $this->getInstance($this->rules[$className]);
            }
            $this->instances[$className] = $this->create($className);
        }
        return $this->instances[$className];
    }
}
