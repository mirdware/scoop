<?php

namespace Scoop\Event;

class Dispatcher
{
    private $bus;

    public function __construct(\Scoop\Event\Bus $bus)
    {
        $this->bus = $bus;
    }

    #[\ReturnTypeWillChange]
    public function dispatch($event)
    {
        $listeners = $this->bus->getListenersForEvent($event);
        $hasStopped = method_exists($event, 'isPropagationStopped');
        foreach ($listeners as $listener) {
            if ($hasStopped && $event->isPropagationStopped()) {
                return $event;
            }
            call_user_func($listener, $event);
        }
        return $event;
    }
}
