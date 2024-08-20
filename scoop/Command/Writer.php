<?php

namespace Scoop\Command;

class Writer
{
    private $stream;

    public function __construct()
    {
        $this->stream = 'php://stdout';
    }
    public function writeError()
    {
        $this->stream = 'php://stderr';
        return call_user_func_array(array($this, 'write'), func_get_args());
    }

    public function write()
    {
        $args = func_get_args();
        if (is_string($args[0])) {
            $args = array($args);
        }
        $std = fopen($this->stream, 'w');
        foreach ($args as $styles) {
            $msg = array_shift($styles);
            fwrite($std, "\e[" . implode(';', $styles) . 'm' . $msg . "\e[0m");
        }
        fwrite($std, PHP_EOL);
        fclose($std);
        $this->stream = 'php://stdout';
        return $this;
    }
}
