<?php

namespace Scoop\Command;

class Writer
{
    private $stream;
    private $separator;
    private $styles = array("\e[0m");
    private $names = array('!>');

    public function __construct($styles)
    {
        $this->stream = 'php://stdout';
        $this->separator = PHP_EOL;
        foreach ($styles as $name => $style) {
            array_push($this->names, "<$name:");
            array_push($this->styles, "\e[" . implode(';', $style) . 'm');
        }
    }
    public function withError()
    {
        if ($this->stream === 'php://stderr') {
            return $this;
        }
        $new = clone $this;
        $new->stream = 'php://stderr';
        return $new;
    }

    public function withSeparator($separator)
    {
        if ($this->separator === $separator) {
            return $this;
        }
        $new = clone $this;
        $new->separator = $separator;
        return $new;
    }

    public function write()
    {
        $args = func_get_args();
        $std = fopen($this->stream, 'w');
        foreach ($args as $msg) {
            fwrite($std, $this->process($msg));
        }
        fclose($std);
        return $this;
    }

    public function process($msg)
    {
        return str_replace($this->names, $this->styles, $msg) . $this->separator;
    }
}
