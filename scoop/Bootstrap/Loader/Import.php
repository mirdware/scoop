<?php

namespace Scoop\Bootstrap\Loader;

class Import
{
    public function load($url)
    {
        return require $url . '.php';
    }
}
