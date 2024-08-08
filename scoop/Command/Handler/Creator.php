<?php

namespace Scoop\Command\Handler;

class Creator
{
    private static $commands = array(
        'struct' => '\Scoop\Command\Creator\Struct'
    );
    private $bus;
    private $writer;

    public function __construct(\Scoop\Command\Writer $writer)
    {
        $this->writer = $writer;
        $this->bus = new \Scoop\Command\Bus(self::$commands);
    }

    public function execute($command)
    {
        $args = $command->getArguments();
        $commandName = array_shift($args);
        $this->bus->dispatch($commandName, $args);
    }

    public function help()
    {
        echo 'create new starter artifacts', PHP_EOL, PHP_EOL,
        'Commands:', PHP_EOL;
        foreach (self::$commands as $command => $controller) {
            echo $command, ' => ', $this->writer->writeLine("$controller.php", \Scoop\Command\Style\Color::BLUE);
        }
        echo PHP_EOL, 'Run app/ice new COMMAND --help for more information', PHP_EOL;
    }
}
