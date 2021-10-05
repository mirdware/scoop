<?php
namespace scoop\Command;

use Exception;

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
            throw new \RuntimeException('Should provider a command');
        }
        if (!isset($this->instances[$name])) {
            if (!isset($this->commands[$name])) {
                throw new \RuntimeException('Command '.$name.' not found');
            }
            $this->instances[$name] = \Scoop\Context::inject($this->commands[$name]);
        }
        return $this->instances[$name];
    }

    public function addCommand($name, $className)
    {
        $baseClass = '\Scoop\Command';
        if (!is_subclass_of($className, $baseClass)) {
            throw new Exception('Class '.$className.' not implements '.$baseClass);
        }
        $this->commands[$name] = $className;
        return $this;
    }
}