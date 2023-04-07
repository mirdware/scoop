<?php

namespace Scoop\Container;

abstract class Injector
{
    private $rules = array();

    public function __construct($environment)
    {
        $this->bind($environment->getConfig('providers', array()));
    }

    public static function formatClassName($className)
    {
        if (strpos($className, '\\') === 0) {
            return substr($className, 1);
        }
        return $className;
    }

    abstract public function has($id);

    abstract protected function getInstance($id);

    abstract protected function setInstance($id, $instance);

    public function get($id)
    {
        $id = self::formatClassName($id);
        if (!$this->has($id)) {
            if (isset($this->rules[$id])) {
                return $this->get($this->rules[$id]);
            }
            $this->create($id);
        }
        return $this->getInstance($id);
    }

    public function create($id, $args = array())
    {
        $index = strpos($id, ':');
        $className = $index === false ? $id : substr($id, 0, $index);
        $class = new \ReflectionClass($className);
        if (!$class->isInstantiable()) {
            throw new \Exception('Cannot inject ' . $className . ' because it cannot be instantiated');
        }
        $constructor = $class->getConstructor();
        if ($constructor) {
            $args = $this->getArguments($constructor->getParameters(), $args);
            $instance = $class->newInstanceArgs($args);
        } else {
            $instance = $class->newInstanceWithoutConstructor();
        }
        $this->setInstance($id, $instance);
        return $instance;
    }

    private function bind($interfaces)
    {
        foreach ($interfaces as $interfaceName => $className) {
            $interfaceName = self::formatClassName($interfaceName);
            $className = self::formatClassName($className);
            $this->rules[$interfaceName] = $className;
        }
    }

    private function getArguments($params, $definitions)
    {
        $args = array();
        foreach ($params as $param) {
            if (isset($definitions[$param->getName()])) {
                $args[] = $definitions[$param->getName()];
            } else {
                $class = method_exists($param, 'getType') ? $param->getType() : $param->getClass();
                if ($class) {
                    $args[] = \Scoop\Context::inject($class->getName());
                }
            }
        }
        return $args;
    }
}
