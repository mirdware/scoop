<?php

namespace Scoop\Command\Handler\Scanner;

class Type
{
    private $writer;

    public function __construct(\Scoop\Command\Writer $writer)
    {
        $this->writer = $writer;
    }

    public function execute($command)
    {
        $composerJson = json_decode(file_get_contents('composer.json'), true);
        $psr4 = $composerJson['autoload']['psr-4'];
        foreach ($psr4 as $namespace => $directory) {
            if (strpos($namespace, 'Scoop\\') !== 0) {
                $directory = rtrim($directory, '/') . '/';
                $prefix = str_replace('\\', '_', $namespace);
                $scanner = new \Scoop\Bootstrap\Scanner\Type($directory, $prefix);
                $this->writer->write(true, "scanning $directory folder... ");
                $this->writer->write(true, "<link:{$scanner->getCacheFilePath()}!> ");
                if ($scanner->scan()) {
                    $this->writer->write('<success:created!>');
                } else {
                    $this->writer->write('<alert:cached!>');
                }
            }
        }
        $this->writer->write('<done:scan finished!!>');
    }

    public function help()
    {
        $this->writer->write(
            'Scan source folder for abstractions and their implementations.'
        );
    }
}
