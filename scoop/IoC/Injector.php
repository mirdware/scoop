<?php
namespace Scoop\IoC;

abstract class Injector
{
    public abstract function create($className, $arguments = array());

    public abstract function bind($interfaceName, $className);

    public abstract function getInstance($className);

    protected function instantiate(\ReflectionClass &$class, $args = array())
    {
        $constructor = $class->getConstructor();
        if ($constructor) {
            $args = array_merge($this->getParams($constructor), $args);
            return $class->newInstanceArgs($args);
        }
        return $class->newInstanceWithoutConstructor();
    }

    protected function getParams(\ReflectionMethod &$method)
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
