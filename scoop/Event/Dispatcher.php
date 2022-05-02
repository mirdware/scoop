<?php
namespace Scoop\Event;

class Dispatcher
{
    private $bus;

    public function __construct($bus)
    {
        $this->bus = $bus;
    }

    public function dispatch($event)
    {
        if ($event->isPropagationStopped()) {
            return $event;
        }
        foreach ($this->bus->getListenersForEvent($event) as $listener) {
            $listener($event);
        }
    }
}
