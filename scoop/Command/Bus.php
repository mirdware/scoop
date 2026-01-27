<?php

namespace Scoop\Command;

class Bus
{
    private $commands = array();
    private $instances = array();

    public function __construct($commands)
    {
        foreach ($commands as $command => $handler) {
            $ref = new \ReflectionClass($handler);
            if (!$ref->hasMethod('help') || !$ref->hasMethod('execute')) {
                throw new \UnexpectedValueException("$handler does not implement help and execute methods", 9901);
            }
            $this->commands[$command] = $handler;
        }
    }

    public function dispatch($name, $args)
    {
        if (!$name) {
            throw new \RuntimeException('Should provider a command see --help', 9904);
        }
        if (!isset($this->instances[$name])) {
            if (!isset($this->commands[$name])) {
                throw new \UnexpectedValueException("Command $name not found", 9904);
            }
            $this->instances[$name] = \Scoop\Context::inject($this->commands[$name]);
        }
        $command = new \Scoop\Command\Request($args);
        if ($command->getOption('help')) {
            $this->instances[$name]->help();
        } else {
            $this->instances[$name]->execute($command);
        }
    }

    public function getCommands()
    {
        return $this->commands;
    }
}
