<?php

namespace Scoop\Log\Factory;

class Handler
{
    private $handlers;
    private $instances;

    public function __construct($handlers)
    {
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
        if (!$this->handlers[$level]) {
            return $this->instances[$level];
        }
        foreach ($this->handlers[$level] as $className => $args) {
            $ref = new \ReflectionClass($className);
            $this->instances[$level][] = $ref->newInstanceArgs((array) $args);
        }
        return $this->instances[$level];
    }
}
