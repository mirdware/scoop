<?php

namespace Scoop\Command\Event;

class ExecutionStarted
{
    private $name;
    private $arguments;
    private $pid;
    private $time;

    public function __construct($name, array $arguments)
    {
        $this->name = $name;
        $this->arguments = $arguments;
        $this->pid = getmypid();
        $this->time = new \DateTime();
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
}
