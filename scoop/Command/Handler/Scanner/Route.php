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
        $cacheFilePath = $this->scanner->getCacheFilePath('routes');
        if ($command->hasFlag('f')) {
            @unlink($cacheFilePath);
            @unlink($this->scanner->getMetaFilePath());
        }
        $this->writer->withSeparator(' ')->write(
            "scanning {$this->scanner->getDirectory()} folder...",
            "<link:{$cacheFilePath}!>"
        );
        if ($this->scanner->scan()) {
            $this->writer->write('<success:created!>');
        } else {
            $this->writer->write('<warn:cached!>');
        }
    }

    public function help()
    {
        $this->writer->write(
            'Scan routes folder searching endpoints and middlewares with correct structure.'
        );
    }
}
