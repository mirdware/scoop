<?php

namespace Scoop\Command\Handler;

class Router
{
    private $bus;
    private $writer;
    private $msg;

    public function __construct($msg, \Scoop\Command\Writer $writer, \Scoop\Command\Bus $bus)
    {
        $this->writer = $writer;
        $this->msg = $msg;
        $this->bus = $bus;
    }

    public function execute($command)
    {
        $args = $command->getArguments();
        $commandName = array_shift($args);
        $this->bus->dispatch($commandName, $args);
    }

    public function help()
    {
        $commands = $this->bus->getCommands();
        $this->writer->write($this->msg, '', 'Commands:');
        foreach ($commands as $command => $controller) {
            $this->writer->write("$command => <link!$controller.php!>");
        }
        $this->writer->write('', 'Run app/ice new COMMAND --help for more information');
    }
}
