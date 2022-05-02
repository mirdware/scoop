<?php
namespace Scoop\Command\Creator;

class Struct extends \Scoop\Command
{
    public function execute($args)
    {
        $this->setArguments($args);
        $path = $this->getPath().date('YmdGisv').$this->getName().'.sql';
        $file = fopen($path, 'w');
        fwrite($file, '');
        fclose($file);
        echo 'File '.$path.' created';
    }

    private function getName()
    {
        $name = $this->getOption('name');
        if (!$name) return '';
        $name = '_'.$name;
        return str_replace(' ', '_', $name);
    }

    private function getPath()
    {
        $path = 'app/structs/'.$this->getOption('schema', '');
        if (strrpos($path, '/') !== strlen($path) - 1) {
            $path .= '/';
        }
        if (!is_dir($path)) {
            mkdir($path, 700, true);
        }
        return $path;
    }
}
