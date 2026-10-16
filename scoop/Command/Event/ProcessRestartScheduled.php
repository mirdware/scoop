<?php

namespace Scoop\Command\Event;

class ProcessRestartScheduled extends ProcessEvent
{
    private $delay;

    public function __construct(\Scoop\Command\Pool\Worker $worker)
    {
        parent::__construct($worker);
        $this->delay = $worker->getDelay();
    }

    public function getDelay()
    {
        return $this->delay;
    }
}
