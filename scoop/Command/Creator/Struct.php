<?php

namespace Scoop\Command\Creator;

class Struct extends \Scoop\Command
{
    protected function execute()
    {
        $path = $this->getPath() . date('YmdGisv') . $this->getName() . '.sql';
        $file = fopen($path, 'w');
        fwrite($file, '');
        fclose($file);
        echo 'File ', self::write($path, \Scoop\Command\Color::BLUE), ' created', PHP_EOL;
    }

    protected function help()
    {
        echo 'Create file of struct on folder app/structs', PHP_EOL, PHP_EOL,
        'Options:', PHP_EOL,
        '--name => add a description to end of genered file', PHP_EOL,
        '--schema => enter the new structure in a "scheme"(folder)', PHP_EOL;
    }

    private function getName()
    {
        $name = $this->getOption('name');
        if (!$name) {
            return '';
        }
        $name = '_' . $name;
        return str_replace(' ', '_', $name);
    }

    private function getPath()
    {
        $path = 'app/structs/' . $this->getOption('schema', '');
        if (strrpos($path, '/') !== strlen($path) - 1) {
            $path .= '/';
        }
        if (!is_dir($path)) {
            mkdir($path, 700, true);
        }
        return $path;
    }
}
