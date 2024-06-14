<?php

namespace scoop\Command;

class Bus
{
    private $commands;
    private $instances;

    public function __construct()
    {
        $this->commands = array();
    }

    public function getCommand($name)
    {
        if (!$name) {
            throw new \RuntimeException('Should provider a command see --help', 101);
        }
        if (!isset($this->instances[$name])) {
            if (!isset($this->commands[$name])) {
                throw new \UnexpectedValueException('Command ' . $name . ' not found', 102);
            }
            $this->instances[$name] = \Scoop\Context::inject($this->commands[$name]);
        }
        return $this->instances[$name];
    }

    public function addCommand($name, $className)
    {
        $baseClass = '\Scoop\Command';
        if (!is_subclass_of($className, $baseClass)) {
            throw new \UnexpectedValueException('Class ' . $className . ' not implements ' . $baseClass, 201);
        }
        $this->commands[$name] = $className;
        return $this;
    }
}
