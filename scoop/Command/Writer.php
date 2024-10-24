<?php

namespace Scoop\Command;

class Writer
{
    private $stream;
    private $styles = array("\e[0m");
    private $names = array('!>');

    public function __construct($styles)
    {
        $this->stream = 'php://stdout';
        foreach ($styles as $name => $style) {
            array_push($this->names, "<$name!");
            array_push($this->styles, "\e[" . implode(';', $style) . 'm');
        }
    }
    public function writeError()
    {
        $this->stream = 'php://stderr';
        return call_user_func_array(array($this, 'write'), func_get_args());
    }

    public function write()
    {
        $args = func_get_args();
        $inline = is_bool($args[0]) ? array_shift($args) : false;
        $std = fopen($this->stream, 'w');
        foreach ($args as $msg) {
            fwrite($std, $this->process($msg, $inline));
        }
        fclose($std);
        $this->stream = 'php://stdout';
        return $this;
    }

    public function process($msg, $inline = false)
    {
        return str_replace($this->names, $this->styles, $msg) . ($inline ? '' : PHP_EOL);
    }
}
