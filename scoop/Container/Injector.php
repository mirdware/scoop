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
        $this->setInstance('Scoop\Bootstrap\Environment', 'singleton', $environment);
        $interfaces = $environment->getConfig('providers', array());
        $providerPath = $environment->getStoragePath('cache/project');
        $providerFiles = glob("{$providerPath}*providers.php");
        foreach ($interfaces as $interfaceName => $className) {
            $interfaceName = self::formatClassName($interfaceName);
            $this->rules[$interfaceName] = $className;
        }
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

    abstract public function clean();

    abstract public function contains($id);

    abstract protected function getInstance($id);

    abstract protected function setInstance($id, $scope, $instance);

    public function get($id)
    {
        $id = self::formatClassName($id);
        if (!$this->contains($id)) {
            list($service, $method, $scope, $inheritance) = $this->normalize($id);
            if (!$service) {
                throw new \Scoop\Container\Exception("provider '$id' must define a valid service name");
            }
            $instance = $this->instantiate($service, $method, $inheritance);
            $this->setInstance($id, $scope, $instance);
            return $instance;
        }
        return $this->getInstance($id);
    }

    public function has($id)
    {
        $id = self::formatClassName($id);
        if ($this->contains($id)) {
            return true;
        }
        list($className, $method) = $this->normalize($id);
        if ($className && $this->getProviders($className) !== false) {
            if ($method) {
                return method_exists($className, $method);
            }
            return true;
        }
        return false;
    }

    private function instantiate($className, $method, $inheritance)
    {
        if (!class_exists($className)) {
            throw new \Scoop\Container\Exception\NotFound("Class $className not found");
        }
        $providers = $this->getProviders($className);
        if ($providers === false) {
            throw new \Scoop\Container\Exception\NotFound("Providers for $className not found");
        }
        if (empty($providers)) {
            $instance = new $className();
        } else {
            $class = new \ReflectionClass($className);
            $instance = $class->newInstanceArgs(array_map(function ($provider) {
                return \Scoop\Context::inject($provider);
            }, $providers));
        }
        if ($method) {
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
        if ($inheritance && !is_a($instance, $inheritance) && !is_subclass_of($instance, $inheritance)) {
            $classIntance = get_class($instance);
            throw new \Scoop\Container\Exception("Object of type $classIntance does not instance of $inheritance", 1105);
        }
        return $instance;
    }

    private function normalize($id)
    {
        $service = $id;
        $scope = 'request';
        $method = null;
        $inheritance = null;
        if (isset($this->rules[$id])) {
            $service = $this->rules[$id];
            $inheritance = $id;
        }
        if (is_string($service)) {
            $service = explode(':', $service);
            if (isset($service[1])) {
                $method = $service[1];
            }
            $service = $service[0];
        } else {
            if (isset($service['method'])) {
                $method = $service['method'];
            }
            if (isset($service['scope'])) {
                $scope = strtolower($service['scope']);
            }
            if (isset($service['service'])) {
                $service = $service['service'];
            }
        }
        $service = is_string($service) ? self::formatClassName($service) : null;
        return array($service, $method, $scope, $inheritance);
    }

    private function getProviders($className)
    {
        if (isset($this->definitions[$className])) {
            return $this->definitions[$className];
        }
        $inspector = new Inspector($className);
        $providers = $inspector->resolveProviders();
        if (!$inspector->isInstantiable() || $providers === false) {
            return false;
        }
        $this->definitions[$className] = $providers;
        return $providers;
    }
}
