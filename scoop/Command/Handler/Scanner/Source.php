<?php

namespace Scoop\Command\Handler\Scanner;

class Source
{
    private $writer;
    private $environment;

    public function __construct(\Scoop\Command\Writer $writer, \Scoop\Bootstrap\Environment $environment)
    {
        $this->writer = $writer;
        $this->environment = $environment;
    }

    public function execute($command)
    {
        $composerJson = json_decode(file_get_contents('composer.json'), true);
        $psr4 = $composerJson['autoload']['psr-4'];
        foreach ($psr4 as $namespace => $directory) {
            $directory = rtrim($directory, '/') . '/';
            $prefix = str_replace('\\', '_', $namespace);
            $scanner = new \Scoop\Bootstrap\Scanner\Source($this->environment, $directory, $prefix);
            $typeFilePath = $scanner->getCacheFilePath('types');
            $providerFilePath = $scanner->getCacheFilePath('providers');
            $this->writer->write("scanning $directory folder:");
            if ($command->hasFlag('f')) {
                @unlink($typeFilePath);
                @unlink($providerFilePath);
                @unlink($scanner->getMetaFilePath());
            }
            if ($scanner->scan()) {
                $this->writer->write(
                    "<link:{$typeFilePath}!> ... <success:created!> ✔️",
                    "<link:{$providerFilePath}!> ... <success:created!> ✔️"
                );
            } else {
                $this->writer->write(
                    "<link:{$typeFilePath}!> ... <warn:cached!> ⚠️",
                    "<link:{$providerFilePath}!> ... <warn:cached!> ⚠️"
                );
            }
        }
        $this->writer->write('<done:scan finished!!>');
    }

    public function help()
    {
        $this->writer->write(
            'Scan source folder for project metadata.'
        );
    }
}
