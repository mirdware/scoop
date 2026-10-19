<?php

namespace Scoop\Command\Handler\Creator;

class Struct
{
    private $writer;
    private $clock;

    public function __construct(\Scoop\Command\Writer $writer, \Scoop\Clock $clock)
    {
        $this->writer = $writer;
        $this->clock = $clock;
    }

    public function execute(\Scoop\Command\Request $command)
    {
        $name = $this->normalizeName($command->getOption('name'));
        $path = $this->getPath($command->getOption('schema', ''));
        $path = $path . $this->clock->now()->format('YmdGisu') . $name . '.sql';
        $file = fopen($path, 'w');
        fwrite($file, '');
        fclose($file);
        $this->writer->write("File <link:$path!> created");
    }

    public function help()
    {
        $this->writer->write(
            'Create file of struct on folder app/structs.',
            '',
            'Options:',
            '--name => add a description to end of genered file',
            '--schema => enter the new structure in a "scheme"(folder)'
        );
    }

    private function normalizeName($name)
    {
        if (!$name) {
            return '';
        }
        $name = '_' . $name;
        return str_replace(' ', '_', $name);
    }

    private function getPath($schema)
    {
        $path = "app/structs/$schema";
        if (strrpos($path, '/') !== strlen($path) - 1) {
            $path .= '/';
        }
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
        return $path;
    }
}
