<?php
namespace Scoop\IoC;

abstract class Injector
{
    protected $rules = array();

    public abstract function getInstance($className);

    public function bind($interfaceName, $className)
    {
        $interfaceName = self::formatClassName($interfaceName);
        $className = self::formatClassName($className);
        if (!is_subclass_of($className, $interfaceName)) {
            throw new \UnexpectedValueException('class '.$className.' can not binding to '.$interfaceName);
        }
        $this->rules[$interfaceName] = $className;
    }

    public function create($className, $args = array())
    {
        $class = new \ReflectionClass($className);
        $constructor = $class->getConstructor();
        if ($constructor) {
            $args = array_merge($this->getArguments($constructor->getParameters()), $args);
            return $class->newInstanceArgs($args);
        }
        return $class->newInstanceWithoutConstructor();
    }

    private function getArguments($params)
    {
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
        if (strpos($className, '\\') === 0) return substr($className, 1);
        return $className;
    }
}
