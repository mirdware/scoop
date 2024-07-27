<?php

namespace Scoop\Command;

class Creator extends \Scoop\Command
{
    private static $commands = array(
        'struct' => '\Scoop\Command\Creator\Struct'
    );
    private $bus;

    public function __construct()
    {
        $this->bus = new Bus(self::$commands);
    }

    protected function execute()
    {
        $args = $this->getArguments();
        $commandName = array_shift($args);
        $this->bus->dispatch($commandName, $args);
    }

    protected function help()
    {
        echo 'create new starter artifacts', PHP_EOL, PHP_EOL,
        'Commands:', PHP_EOL;
        foreach (self::$commands as $command => $controller) {
            echo $command, ' => ', self::writeLine($controller . '.php', Color::BLUE);
        }
        echo PHP_EOL, 'Run app/ice new COMMAND --help for more information', PHP_EOL;
    }
}
