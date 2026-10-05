<?php

namespace Scoop\Command\Handler\Cleaner;

class View
{
    private $writer;
    private $directory;
    private $context;

    public function __construct(
        \Scoop\Command\Writer $writer,
        \Scoop\Bootstrap\Environment $context,
        \Scoop\Command\Directory $directory
    ) {
        $this->writer = $writer;
        $this->directory = $directory;
        $this->context = $context;
    }

    public function execute()
    {
        $viewStorage = $this->context->getStoragePath('cache/views');
        if ($this->directory->delete($viewStorage)) {
            return $this->writer->write('View cache cleaned <success:successfully!>.');
        }
        $this->writer->write('<info:Nothing to clean.!>');
    }

    public function help()
    {
        $this->writer->write('Completely removes all view files cached.');
    }
}
