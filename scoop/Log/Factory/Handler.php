<?php

namespace Scoop\Log\Factory;

class Handler
{
    private $injector;
    private $handlers;

    public function __construct(\Scoop\Container\Injector $injector, $handlers)
    {
        $this->injector = $injector;
        $this->handlers = $handlers;
    }

    public function create($level)
    {
        if (!defined('\\Scoop\\Log\\Level::' . strtoupper($level))) {
            throw new \InvalidArgumentException("$level not support level");
        }
        if (array_key_exists($level, $this->handlers) && empty($this->handlers[$level])) {
            return array();
        }
        $handlers = array_merge(
            isset($this->handlers['all']) ? $this->handlers['all'] : array(),
            isset($this->handlers[$level]) ? $this->handlers[$level] : array()
        );
        if (empty($handlers)) {
            $handlers = array('Scoop\Log\Handler\Standard' => array());
        }
        $instances = array();
        foreach ($handlers as $className => $args) {
            try {
                $instances[] = $this->createHandlerInstance($className, $args);
            } catch (\Exception $error) {
                error_log("Exception writing log: $error");
            } catch (\Throwable $error) {
                error_log("Error writing log: $error");
            }
        }
        return $instances;
    }

    private function createHandlerInstance($className, $args)
    {
        if (!class_exists($className)) {
            throw new \InvalidArgumentException(
                "Handler class '$className' does not exist"
            );
        }
        if (!is_array($args)) {
            $args = array();
        }
        $args = $this->prepareHandlerArguments($args);
        $reflection = new \ReflectionClass($className);
        $constructor = $reflection->getConstructor();
        if ($constructor) {
            return $reflection->newInstanceArgs($this->mapConstructorParameters($constructor->getParameters(), $args));
        }
        return $reflection->newInstance();
    }

    private function mapConstructorParameters($parameters, $args)
    {
        $params = array();
        foreach ($parameters as $param) {
            $name = $param->getName();
            if (array_key_exists($name, $args)) {
                $params[] = $args[$name];
            } else if ($provider = $this->getParameterProvider($param)) {
                $params[] = $this->injector->get($provider);
            } else if ($param->isDefaultValueAvailable()) {
                $params[] = $param->getDefaultValue();
            } else {
                throw new \InvalidArgumentException("Missing required constructor parameter: $name");
            }
        }
        return $params;
    }

    private function getParameterProvider($parameter)
    {
        if (!method_exists($parameter, 'getType')) {
            $class = $parameter->getClass();
            return $class ? $class->getName() : null;
        }
        $type = $parameter->getType();
        if (!$type || !method_exists($type, 'isBuiltin') || $type->isBuiltin()) {
            return null;
        }
        $name = method_exists($type, 'getName') ? $type->getName() : (string) $type;
        if ($name === 'self' || $name === 'static') {
            return $parameter->getDeclaringClass()->getName();
        }
        if ($name === 'parent') {
            $parent = $parameter->getDeclaringClass()->getParentClass();
            return $parent ? $parent->getName() : null;
        }
        return ltrim($name, '\\');
    }

    private function prepareHandlerArguments(array $args)
    {
        if (!isset($args['formatter'])) {
            $args['formatter'] = 'Scoop\Log\Formatter';
        }
        if (is_string($args['formatter'])) {
            $args['formatter'] = $this->injector->get($args['formatter']);
        }
        return $args;
    }
}
