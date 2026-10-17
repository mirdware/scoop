<?php

namespace Scoop\Command\Event;

class ExecutionFinished
{
    private $name;
    private $arguments;
    private $pid;
    private $time;
    private $exitCode;
    private $error;

    public function __construct($name, array $arguments, $exitCode, $error = null)
    {
        $this->name = $name;
        $this->arguments = $arguments;
        $this->pid = getmypid();
        $this->time = new \DateTime();
        $this->exitCode = $exitCode;
        $this->error = $error;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getArguments()
    {
        return $this->arguments;
    }

    public function getPid()
    {
        return $this->pid;
    }

    public function getTime()
    {
        return $this->time;
    }

    public function getExitCode()
    {
        return $this->exitCode;
    }

    public function getError()
    {
        return $this->error;
    }
}
