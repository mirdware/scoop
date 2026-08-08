<?php

namespace Scoop\Container;

abstract class Injector
{
    private $rules = array();
    private $definitions = array();
    private $environment;

    public function __construct($environment)
    {
        $this->environment = $environment;
        $this->setInstance('Scoop\Bootstrap\Environment', $environment);
        $this->bind($environment->getConfig('providers', array()));
        $providerPath = $environment->getStoragePath('cache/project');
        $providerFiles = glob("{$providerPath}*providers.php");
        foreach ($providerFiles as $file) {
            $this->definitions += require $file;
        }
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
        $definition = explode(':', $id);
        $className = $definition[0];
        $instance = $this->instantiate($className);
        if (isset($definition[1])) {
            $method = $definition[1];
            if (!is_callable(array($instance, $method))) {
                throw new \Scoop\Container\Exception\NotFound("Factory method $className:$method not found");
            }
            $instance = $instance->$method();
            if (!is_object($instance)) {
                $type = gettype($instance);
                throw new \Scoop\Container\Exception(
                    "Factory method $className:$method returned $type and must resolve to an object instance."
                );
            }
        }
        if ($inheritance) {
            if (!is_a($instance, $inheritance) && !is_subclass_of($instance, $inheritance)) {
                $classIntance = get_class($instance);
                throw new \Scoop\Container\Exception("Object of type $classIntance does not instance of $inheritance", 1105);
            }
            $id = $inheritance;
        }
        $this->setInstance($id, $instance);
        return $instance;
    }

    private function instantiate($className)
    {
        if (!class_exists($className)) {
            throw new \Scoop\Container\Exception\NotFound("Class $className not found");
        }
        $providers = isset($this->definitions[$className]) ?
        $this->definitions[$className] :
        $this->getReflectionProviders($className);
        if (empty($providers)) {
            $instance = new $className();
        } else {
            $class = new \ReflectionClass($className);
            $instance = $class->newInstanceArgs(array_map(function ($provider) {
                return \Scoop\Context::inject($provider);
            }, $providers));
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

    private function getReflectionProviders($className)
    {
        $class = new \ReflectionClass($className);
        if (!$class->isInstantiable()) {
            throw new \Scoop\Container\Exception\NotFound("Providers for $className not found");
        }
        $constructor = $class->getConstructor();
        if (!$constructor) {
            return array();
        }
        $providers = array();
        $usesDefault = false;
        $parameters = $constructor->getParameters();
        foreach ($parameters as $parameter) {
            $provider = $this->getParameterClass($parameter, $class);
            $isDefault = $parameter->isDefaultValueAvailable();
            if ($provider && !$usesDefault) {
                $providers[] = $provider;
                continue;
            }
            if (!$provider && $isDefault) {
                $usesDefault = true;
                continue;
            }
            throw new \Scoop\Container\Exception\notFound("Providers for $className not found");
        }
        $this->definitions[$className] = $providers;
        return $providers;
    }

    private function getParameterClass($parameter, $class)
    {
        if (!method_exists($parameter, 'getType')) {
            $provider = $parameter->getClass();
            return $provider ? $provider->getName() : null;
        }
        $type = $parameter->getType();
        if (!$type || !method_exists($type, 'isBuiltin') || $type->isBuiltin()) {
            return null;
        }
        $provider = method_exists($type, 'getName') ? $type->getName() : (string) $type;
        if ($provider === 'self') {
            return $class->getName();
        }
        if ($provider === 'parent') {
            $parent = $class->getParentClass();
            return $parent ? $parent->getName() : null;
        }
        return ltrim($provider, '\\');
    }
}
