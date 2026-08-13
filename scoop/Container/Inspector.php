<?php

namespace Scoop\Container;

class Inspector
{
    private $class;

    public function __construct($className)
    {
        if (class_exists($className) || interface_exists($className)) {
            $this->class = new \ReflectionClass($className);
        }
    }

    public function isInstantiable()
    {
        return $this->class && $this->class->isInstantiable();
    }

    public function resolveProviders()
    {
        $providers = $this->getOwnProviders();
        if ($providers !== null) {
            return $providers;
        }
        $parent = $this->class->getParentClass();
        if (!$parent) {
            return array();
        }
        $inspector = new self($parent->getName());
        return $inspector->resolveProviders();
    }

    private function getOwnProviders()
    {
        if (!$this->class) {
            return false;
        }
        $constructor = $this->class->getConstructor();
        if (!$constructor || $constructor->getDeclaringClass()->getName() !== $this->class->getName()) {
            return null;
        }
        return $this->getProviders($constructor);
    }

    private function getProviders($constructor)
    {
        if (!$constructor->isPublic()) {
            return false;
        }
        $providers = array();
        $usesDefault = false;
        $class = $constructor->getDeclaringClass();
        foreach ($constructor->getParameters() as $parameter) {
            $provider = $this->getProvider($parameter, $class);
            if ($provider) {
                if ($usesDefault) {
                    return false;
                }
                $providers[] = $provider;
            } elseif ($parameter->isDefaultValueAvailable()) {
                $usesDefault = true;
            } else {
                return false;
            }
        }
        return $providers;
    }

    private function getProvider($parameter, $class)
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
        if ($provider === 'self' || $provider === 'static') {
            return $class->getName();
        }
        if ($provider === 'parent') {
            $parent = $class->getParentClass();
            return $parent ? $parent->getName() : null;
        }
        return ltrim($provider, '\\');
    }
}
