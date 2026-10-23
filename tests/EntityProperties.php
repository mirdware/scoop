<?php

namespace Test;

use PHPStan\Reflection\ExtendedPropertyReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Properties\ReadWritePropertiesExtension;

final class EntityProperties implements ReadWritePropertiesExtension
{
    private $reflectionProvider;
    private $entities;

    public function __construct(ReflectionProvider $reflectionProvider, string $entitiesFile)
    {
        $this->reflectionProvider = $reflectionProvider;
        $mapping = $this->load($entitiesFile);
        $this->entities = isset($mapping['entities']) ? $this->resolve($mapping['entities']) : $mapping;
    }

    public function isAlwaysWritten(ExtendedPropertyReflection $property, string $propertyName): bool
    {
        foreach ($this->entities as $className => $mapping) {
            if (!isset($mapping['properties'][$propertyName]) || !$this->reflectionProvider->hasClass($className)) {
                continue;
            }
            $class = $this->reflectionProvider->getClass($className);
            while (!$class->hasNativeProperty($propertyName) && $class->getParentClass() !== null) {
                $class = $class->getParentClass();
            }
            if (!$class->hasNativeProperty($propertyName)) {
                continue;
            }
            $mappedProperty = $class->getNativeProperty($propertyName);
            if ($mappedProperty->getDeclaringClass()->getName() === $property->getDeclaringClass()->getName()) {
                return true;
            }
        }
        return false;
    }

    public function isAlwaysRead(ExtendedPropertyReflection $property, string $propertyName): bool
    {
        return false;
    }

    public function isInitialized(ExtendedPropertyReflection $property, string $propertyName): bool
    {
        return false;
    }

    private function resolve($value): array
    {
        if (is_array($value)) {
            return $value;
        }
        if (is_string($value) && strpos($value, 'import:') === 0) {
            return $this->load(substr($value, 7));
        }
        throw new \UnexpectedValueException('Entity metadata must be an array or an import: path.');
    }

    private function load(string $path): array
    {
        if (substr($path, -4) !== '.php') {
            $path .= '.php';
        }
        if (!preg_match('~^(?:[A-Za-z]:[/\\\\]|/)~', $path)) {
            $path = dirname(__DIR__) . '/' . $path;
        }
        if (!is_file($path)) {
            return array();
        }
        $mapping = require $path;
        if (!is_array($mapping)) {
            throw new \UnexpectedValueException('Entity configuration must return an array: ' . $path);
        }
        return $mapping;
    }
}
