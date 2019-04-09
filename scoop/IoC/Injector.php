<?php
namespace Scoop\IoC;

abstract class Injector
{
    public abstract function bind($interfaceName, $className);

    public abstract function getInstance($className);

    public function create($className, $args = array())
    {
        $class = new \ReflectionClass($className);
        $constructor = $class->getConstructor();
        if ($constructor) {
            return $class->newInstanceArgs($this->getArguments($constructor) + $args);
        }
        return $class->newInstanceWithoutConstructor();
    }

    protected function getArguments(\ReflectionMethod $method)
    {
        $params = $method->getParameters();
        $args = array();
        foreach ($params as $param) {
            $class = $param->getClass();
            if ($class) {
                $args[] = $this->getInstance($class->getName());
            }
        }
        return $args;
    }

    protected static function formatClassName($className)
    {
        if (strpos($className, '\\') === 0) {
            return substr($className, 1);
        }
        return $className;
    }
}
