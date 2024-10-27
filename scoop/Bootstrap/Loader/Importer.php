<?php

namespace Scoop\Bootstrap\Loader;

class Importer
{
    public function load($url)
    {
        return require $url . '.php';
    }
}
