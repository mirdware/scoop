<?php

namespace Scoop\Command\Event;

abstract class ProcessEvent
{
    private $command;
    private $replica;
    private $pid;
    private $time;
    private $starts;

    public function __construct(\Scoop\Command\Pool\Worker $worker)
    {
        $this->command = $worker->getCommand();
        $this->replica = $worker->getReplica();
        $this->pid = $worker->getPid();
        $this->time = new \DateTime();
        $this->starts = $worker->getStartCount();
    }

    public function getStartCount()
    {
        return $this->starts;
    }

    public function getCommand()
    {
        return $this->command;
    }

    public function getReplica()
    {
        return $this->replica;
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
