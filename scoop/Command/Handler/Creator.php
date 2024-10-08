<?php

namespace Scoop\Command\Handler;

class Creator
{
    private static $commands = array(
        'struct' => 'Scoop\Command\Handler\Creator\Struct'
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
        $this->writer->write(
            array('create new starter artifacts' . PHP_EOL),
            array(PHP_EOL . 'Commands:')
        );
        foreach (self::$commands as $command => $controller) {
            $this->writer->write(
                array("$command => "),
                array("$controller.php", \Scoop\Command\Style\Color::BLUE)
            );
        }
        $this->writer->write(PHP_EOL . 'Run app/ice new COMMAND --help for more information');
    }
}
