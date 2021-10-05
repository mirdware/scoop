<?php
namespace Scoop\Command\Creator;

class Struct extends \Scoop\Command
{
    public function execute($args)
    {
        $path = 'app/structs/'.date('YmdGisv').'.sql';
        $file = fopen($path, 'w');
        fwrite($file, '');
        fclose($file);
        echo 'File '.$path.' created';
    }
}
