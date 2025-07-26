<?php

namespace Scoop\Command\Handler;

class PreLoader
{
    private $writer;
    private $environment;

    public function __construct(
        \Scoop\Command\Writer $writer,
        \Scoop\Bootstrap\Environment $environment
    ) {
        $this->writer = $writer;
        $this->environment = $environment;
    }

    public function execute($command)
    {
        $args = $command->getArguments();
        $this->environment->loadLazily($args[0]);
        $this->writer->write("<warn:{$args[0]}!> loaded successfully.");
    }

    public function help()
    {
        $this->writer->write(
            'Simulates loading a configuration system file.',
            '',
            'Arguments:',
            'File to load in format type:file'
        );
    }
}
