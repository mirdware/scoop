<?php

namespace Scoop\Command\Handler\Scanner;

class Route
{
    private $writer;

    public function __construct(\Scoop\Command\Writer $writer)
    {
        $this->writer = $writer;
    }

    public function execute($command)
    {
        $pathRoutes = 'app/routes/';
        $this->writer->write(true, "scanning $pathRoutes folder... ");
        $scanner = new \Scoop\Bootstrap\Scanner\Route($pathRoutes);
        $this->writer->write('<link!' . $scanner->scan() . '!> <success!created!>');
    }

    public function help()
    {
        $this->writer->write(
            'Scan routes folder searching files route.php with correct structure.'
        );
    }
}
