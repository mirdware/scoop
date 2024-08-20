<?php

namespace Scoop;

/**
 * @deprecated [7.4] use execute and help public methods
 */
abstract class Command
{
    private $options = array();
    private $flags = array();
    private $arguments = array();

    public function run($args)
    {
        $this->setArguments($args);
        if ($this->getOption('help')) {
            return $this->help();
        }
        return $this->execute();
    }

    public static function writeLine()
    {
        echo call_user_func_array(array('\Scoop\Command', 'write'), func_get_args()), PHP_EOL;
    }

    public static function write()
    {
        $styles = func_get_args();
        $msg = array_shift($styles);
        if (!is_string($msg)) {
            throw new \InvalidArgumentException('first parameter should be string');
        }
        echo "\e[", implode(';', $styles), 'm', $msg, "\e[0m";
    }

    protected function getArguments()
    {
        return $this->arguments;
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

    abstract protected function execute();

    abstract protected function help();

    private function setArguments($args)
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
}
