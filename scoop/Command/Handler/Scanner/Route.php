<?php

namespace Scoop\Command\Handler\Scanner;

class Route
{
    private $writer;
    private $scanner;

    public function __construct(\Scoop\Command\Writer $writer, \Scoop\Bootstrap\Scanner\Route $scanner)
    {
        $this->writer = $writer;
        $this->scanner = $scanner;
    }

    public function execute($command)
    {
        $pathRoutes = 'app/routes/';
        $this->writer->write(true, "scanning {$this->scanner->getDirectory()} folder... ");
        $this->writer->write(true, "<link: {$this->scanner->getCacheFilePath()}!> ");
        if ($this->scanner->scan()) {
            $this->writer->write('<success:created!>');
        } else {
            $this->writer->write('<alert:cached!>');
        }
    }

    public function help()
    {
        $this->writer->write(
            'Scan routes folder searching files route.php with correct structure.'
        );
    }
}
