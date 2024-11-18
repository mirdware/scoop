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
        $storagePath = \Scoop\Context::getEnvironment()->getConfig('storage', 'app/storage/');
        $psr4 = $composerJson['autoload']['psr-4'];
        foreach ($psr4 as $namespace => $directory) {
            if (strpos($namespace, 'Scoop\\') === 0) {
                continue;
            }
            $directory = rtrim($directory, '/') . '/';
            $this->writer->write(true, "scanning $directory folder... ");
            $scanner = new \Scoop\Bootstrap\Scanner\Type($directory);
            $this->writer->write('<link!' . $scanner->scan() . '!> <success!created!>');
        }
        $this->writer->write('<done!scan finished!!>');
    }

    public function help()
    {
        $this->writer->write(
            'Scan source folder for abstractions and their implementations.'
        );
    }
}
