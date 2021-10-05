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
            if ($endofoptions) {
                $this->arguments[] = $arg;
                continue;
            }
            if (substr( $arg, 0, 2 ) === '--') {
                if (!isset ($arg[3])) {
                    $endofoptions = true;
                    continue;
                }
                $value = "";
                $com   = substr( $arg, 2 );
                if (strpos($com,'=')) {
                    list($com,$value) = explode('=', $com, 2);
                } elseif (strpos($args[0],'-') !== 0) {
                    while (strpos($args[0],'-') !== 0) {
                        $value .= array_shift($args).' ';
                    }
                    $value = rtrim($value,' ');
                }
                $this->options[$com] = !empty($value) ? $value : true;
                continue;
            }
            if ( substr( $arg, 0, 1 ) === '-' ) {
                for ($i = 1; isset($arg[$i]) ; $i++) {
                    $this->flags[] = $arg[$i];
                }
                continue;
            }
            $this->commands[] = $arg;
            continue;
        }
        if (!count($this->options) && !count($this->flags)) {
            $this->arguments = array_merge($this->commands, $this->arguments);
            $this->commands = array();
        }
    }
}
