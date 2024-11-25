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
            $this->instantiate($level);
        }
        return $this->instances[$level];
    }

    private function instantiate($level)
    {
        foreach ($this->handlers[$level] as $className => $args) {
            $ref = new \ReflectionClass($className);
            if (!isset($args['formatter'])) {
                $args['formatter'] = 'Scoop\Log\Formatter';
            }
            if ($className === 'Scoop\Log\Handler\File' && !isset($args['file'])) {
                $args['file'] = $this->logPath;
            }
            $args['formatter'] = \Scoop\Context::inject($args['formatter']);
            $this->instances[$level][] = $ref->newInstanceArgs((array) $args);
        }
    }
}
