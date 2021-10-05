<?php
namespace Scoop\Command;

class Creator extends \Scoop\Command
{
    private $bus;

    public function __construct()
    {
        $this->bus = new Bus();
        $this->bus->addCommand('struct', Creator\Struct::class);
    }

    public function execute($args)
    {
        $commandName = array_shift($args);
        return $this->bus->getCommand($commandName)->execute($args);
    }
}
