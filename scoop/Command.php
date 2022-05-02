<?php
namespace Scoop;

abstract class Command
{
    private $commands = array();
    private $options = array();
    private $flags = array();
    private $arguments = array();

    public abstract function execute($args);

    protected function setArguments($args)
    {
        $endofoptions = false;
        while ($arg = array_shift($args)) {
            $endofoptions = $this->setOptions($arg, $endofoptions);
        }
        if (!count($this->options) && !count($this->flags)) {
            $this->arguments = array_merge($this->commands, $this->arguments);
            $this->commands = array();
        }
    }

    private function setOptions($arg, $endofoptions)
    {
        if ($endofoptions) {
            $this->arguments[] = $arg;
            return true;
        }
        if (substr($arg, 0, 2) === '--') {
            if (!isset ($arg[3])) return true;
            $value = '';
            $com = substr( $arg, 2 );
            if (strpos($com, '=')) {
                list($com, $value) = explode('=', $com, 2);
            }
            $this->options[$com] = !empty($value) ? $value : true;
            return false;
        }
        if (substr($arg, 0, 1) === '-') {
            for ($i = 1; isset($arg[$i]) ; $i++) {
                $this->flags[] = $arg[$i];
            }
            return false;
        }
        $this->commands[] = $arg;
        return false;
    }

    protected function getOption($name, $default = null)
    {
        if (isset($this->options[$name])) {
            return $this->options[$name];
        }
        return $default;
    }

    protected function hasFlag($name)
    {
        return in_array($name, $this->flags);
    }
}
