<?php

namespace Scoop\Bootstrap\Loader;

class JsonParser
{
    public function load($url)
    {
        return json_decode(file_get_contents($url . '.json'), true);
    }
}
