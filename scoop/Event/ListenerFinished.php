<?php

namespace Scoop\Event;

class ListenerFinished
{
    private $listener;
    private $event;
    private $error;

    public function __construct($listener, $event, $error)
    {
        $this->listener = $listener;
        $this->event = $event;
        $this->error = $error;
    }

    public function getListener()
    {
        return $this->listener;
    }

    public function getEvent()
    {
        return $this->event;
    }

    public function getError()
    {
        return $this->error;
    }

    public function hasFailed()
    {
        return $this->error !== null;
    }
}
