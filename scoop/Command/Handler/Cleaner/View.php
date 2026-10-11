<?php

namespace Scoop\Command\Handler\Cleaner;

class View
{
    private $context;
    private $writer;
    private $directory;

    public function __construct(
        \Scoop\Context $context,
        \Scoop\Command\Writer $writer,
        \Scoop\Command\Directory $directory
    ) {
        $this->context = $context;
        $this->writer = $writer;
        $this->directory = $directory;
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
