<?php

namespace Scoop\Command;

class Writer
{
    public function writeLine()
    {
        echo call_user_func_array(array($this, 'write'), func_get_args()), PHP_EOL;
    }

    public function write()
    {
        $styles = func_get_args();
        $msg = array_shift($styles);
        if (!is_string($msg)) {
            throw new \InvalidArgumentException('first parameter should be string');
        }
        echo "\e[", implode(';', $styles), 'm', $msg, "\e[0m";
    }
}
