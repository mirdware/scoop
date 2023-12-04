<?php

namespace Scoop\Event;

class Dispatcher
{
    private $bus;

    public function __construct(Bus $bus)
    {
        $this->bus = $bus;
    }

    public function dispatch($event)
    {
        if (method_exists($event, 'isPropagationStopped') && $event->isPropagationStopped()) {
            return $event;
        }
        foreach ($this->bus->getListenersForEvent($event) as $listener) {
            $listener->listen($event);
        }
    }
}
