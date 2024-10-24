<?php

namespace Scoop\Container;

abstract class Injector
{
    private $rules = array();

    public function __construct($environment)
    {
        $this->bind($environment->getConfig('providers', array()) + array(
            '\Scoop\Event\Bus' => '\Scoop\Event\Factory\Bus:create',
            '\Scoop\Log\Logger' => '\Scoop\Log\Factory\Logger:create',
            '\Scoop\Command\Bus' => '\Scoop\Command\Factory\Bus:create',
            '\Scoop\Command\Writer' => '\Scoop\Command\Factory\Writer:create',
            '\Scoop\Persistence\Vault' => '\Scoop\Persistence\Factory\Vault:create',
            '\Scoop\Persistence\Entity\Manager' => '\Scoop\Persistence\Factory\EntityManager:create'
        ));
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
                return $this->create($this->rules[$id], $id);
            }
            return $this->create($id);
        }
        return $this->getInstance($id);
    }

    public function create($id, $inheritance = null)
    {
        $method = explode(':', $id);
        $instance = $this->instantiate($method[0], isset($method[1]) ? $method[1] : null);
        if ($inheritance) {
            if (!is_a($instance, $inheritance) && !is_subclass_of($instance, $inheritance)) {
                $className = get_class($instance);
                throw new \Scoop\Container\Exception("Object of type $className does not instance of $inheritance", 1105);
            }
            $id = $inheritance;
        }
        $this->setInstance($id, $instance);
        return $instance;
    }

    private function instantiate($className, $method)
    {
        if (!class_exists($className)) {
            throw new \Scoop\Container\Exception\NotFound("Class $className not found");
        }
        $class = new \ReflectionClass($className);
        if (!$class->isInstantiable()) {
            throw new \Scoop\Container\Exception("Cannot inject $className because it cannot be instantiated", 1101);
        }
        $constructor = $class->getConstructor();
        if ($constructor) {
            $args = $this->getArguments($constructor->getParameters());
            $instance = $class->newInstanceArgs($args);
        } else {
            $instance = $class->newInstanceWithoutConstructor();
        }
        if ($method && $class->hasMethod($method)) {
            $instance = $class->getMethod($method)->invoke($instance);
        }
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

    private function getArguments($params)
    {
        $args = array();
        foreach ($params as $param) {
            $class = method_exists($param, 'getType') ? $param->getType() : $param->getClass();
            if ($class) {
                $args[] = \Scoop\Context::inject($class->getName());
            }
        }
        return $args;
    }
}
