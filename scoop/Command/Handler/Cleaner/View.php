<?php

namespace Scoop\Command\Handler\Cleaner;

class View
{
    private $writer;
    private $directory;
    private $viewStorage;

    public function __construct(
        \Scoop\Command\Writer $writer,
        \Scoop\Bootstrap\Environment $environment,
        \Scoop\Command\Directory $directory
    ) {
        $this->writer = $writer;
        $this->directory = $directory;
        $this->viewStorage = $environment->getConfig('storage', 'app/storage');
    }

    public function execute()
    {
        if ($this->directory->delete($this->viewStorage . '/cache/views/')) {
            return $this->writer->write('View cache cleaned <success:successfully!>.');
        }
        $this->writer->write('<info:Nothing to clean.!>');
    }

    public function help()
    {
        $this->writer->write('Completely removes all view files cached.');
    }
}
