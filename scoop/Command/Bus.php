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
        $this->commands = array();
        foreach ($commands as $command => $handler) {
            $ref = new \ReflectionClass($handler);
            $isValidHandler = $ref->hasMethod('help') && $ref->hasMethod('execute');
            if (!$ref->isSubclassOf($baseClass) && !$isValidHandler) {
                throw new \UnexpectedValueException("Class $handler not implements $baseClass", 9901);
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
