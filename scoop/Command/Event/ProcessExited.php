<?php

namespace Scoop\Command\Event;

class ProcessExited extends ProcessEvent
{
    private $exitCode;

    public function __construct(\Scoop\Command\Pool\Worker $worker)
    {
        parent::__construct($worker);
        $this->exitCode = $worker->getExitCode();
    }

    public function getExitCode()
    {
        return $this->exitCode;
    }
}
