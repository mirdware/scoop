<?php
namespace Scoop;

use InvalidArgumentException;

abstract class Command
{
    private $options = array();
    private $flags = array();
    private $arguments = array();

    public function run($args)
    {
        if (isset($args[0]) && $args[0] === '--help') {
            return $this->help();
        }
        $this->setArguments($args);
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
        if (!is_string($msg)) throw new \InvalidArgumentException('first parameter should be string');
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

    protected abstract function execute();

    protected abstract function help();

    private function setArguments($args)
    {
        $this->arguments = $args;
        while ($arg = array_shift($args)) {
            if (strlen($arg) > 2 && substr($arg, 0, 2) === '--') {
                $value = '';
                $com = substr($arg, 2);
                if (strpos($com, '=')) {
                    list($com, $value) = explode('=', $com, 2);
                }
                $this->options[$com] = !empty($value) ? $value : true;
            } elseif (substr($arg, 0, 1) === '-') {
                for ($i = 1; isset($arg[$i]) ; $i++) {
                    $this->flags[] = $arg[$i];
                }
            }
        }
    }
}
