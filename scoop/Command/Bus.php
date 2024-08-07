<?php

namespace scoop\Command;

class Bus
{
    private $commands;
    private $instances;

    public function __construct($commands)
    {
        /**
         * @deprecated [7.4]
         */
        $baseClass = '\Scoop\Command';
        $interface = '\Scoop\Command\Handler';
        $this->commands = array();
        foreach ($commands as $command => $controller) {
            if (!is_subclass_of($controller, $baseClass) && !is_subclass_of($controller, $interface)) {
                throw new \UnexpectedValueException("Class $controller not implements $baseClass", 201);
            }
            $this->commands[$command] = $controller;
        }
    }

    public function dispatch($name, $args)
    {
        if (!$name) {
            throw new \RuntimeException('Should provider a command see --help', 101);
        }
        if (!isset($this->instances[$name])) {
            if (!isset($this->commands[$name])) {
                throw new \UnexpectedValueException("Command $name not found", 102);
            }
            $this->instances[$name] = \Scoop\Context::inject($this->commands[$name]);
        }
        /**
         * @deprecated [7.4]
         */
        if (is_subclass_of($this->instances[$name], '\Scoop\Command')) {
            $this->instances[$name]->run($args);
        } else {
            if (isset($args['--help'])) {
                $this->instances[$name]->help();
            } else {
                $command = new \Scoop\Command\Request($args);
                $this->instances[$name]->execute($command);
            }
        }
    }
}
