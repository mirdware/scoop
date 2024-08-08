<?php

namespace Scoop\Command;

class Request
{
    private $options = array();
    private $flags = array();
    private $arguments = array();

    public function __construct($args)
    {
        while ($arg = array_shift($args)) {
            if (strpos($arg, '-') !== 0) {
                array_unshift($args, $arg);
                $this->arguments = $args;
                return;
            }
            if (strpos($arg, '--') !== 0) {
                $arg = substr($arg, 1);
                if ($arg) {
                    $this->flags = str_split($arg);
                }
                continue;
            }
            $arg = substr($arg, 2);
            if ($arg) {
                $arg = explode('=', $arg, 2);
                $this->options[$arg[0]] = isset($arg[1]) ? $arg[1] : true;
            }
        }
    }

    public function getArguments()
    {
        return $this->arguments;
    }

    public function getOption($name, $default = null)
    {
        if (isset($this->options[$name])) {
            return $this->options[$name];
        }
        return $default;
    }

    public function hasFlag($name)
    {
        return in_array($name, $this->flags);
    }
}
