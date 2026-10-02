<?php

namespace Scoop\Event;

/**
 * @deprecated
 * @see Event middlewares
 * @since 0.8.1
 */
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
