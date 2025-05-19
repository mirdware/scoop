<?php

namespace Scoop\Log\Factory;

class Handler
{
    private $logPath;
    private $handlers;
    private $instances;

    public function __construct($handlers, $logPath)
    {
        $this->logPath = $logPath;
        $this->handlers = $handlers;
        $this->instances = array();
    }

    public function create($level)
    {
        if (!defined('\Scoop\Log\Level::' . strtoupper($level))) {
            throw new \InvalidArgumentException("$level not support level");
        }
        if (isset($this->instances[$level])) {
            return $this->instances[$level];
        }
        $this->instances[$level] = array();
        if (isset($this->handlers['all'])) {
            $this->handlers[$level] = array_merge(
                $this->handlers['all'],
                isset($this->handlers[$level]) ? $this->handlers[$level] : array()
            );
        }
        if (!empty($this->handlers[$level])) {
            $this->createHandler($level);
        }
        return $this->instances[$level];
    }

    private function createHandler($level)
    {
        if (!isset($this->handlers[$level]) || !is_array($this->handlers[$level])) {
            return;
        }
        foreach ($this->handlers[$level] as $className => $args) {
            $instance = $this->createHandlerInstance($className, $args);
            if ($instance !== null) {
                $this->instances[$level][] = $instance;
            }
        }
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
        $args = $this->prepareHandlerArguments($className, $args);
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
            } else if ($param->isDefaultValueAvailable()) {
                $params[] = $param->getDefaultValue();
            } else {
                throw new \InvalidArgumentException("Missing required constructor parameter: $name");
            }
        }
        return $params;
    }

    private function prepareHandlerArguments($className, array $args)
    {
        if (!isset($args['formatter'])) {
            $args['formatter'] = 'Scoop\Log\Formatter';
        }
        if ($className === 'Scoop\Log\Handler\File' && !isset($args['file'])) {
            $args['file'] = $this->logPath;
        }
        $args['formatter'] = \Scoop\Context::inject($args['formatter']);
        return $args;
    }
}
