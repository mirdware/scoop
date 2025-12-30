<?php

namespace Scoop\Event;

class ListenerStarted
{
    private $listener;
    private $event;

    public function __construct($listener, $event)
    {
        $this->listener = $listener;
        $this->event = $event;
    }

    public function getListener()
    {
        return $this->listener;
    }

    public function getEvent()
    {
        return $this->event;
    }
}
