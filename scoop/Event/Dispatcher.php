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
        $eventBase = 'Scoop\Event';
        if (!is_subclass_of($event, $eventBase)) {
            throw new \InvalidArgumentException(get_class($event).' not implements '.$eventBase);
        }
        if ($event->isPropagationStopped()) {
            return $event;
        }
        foreach ($this->bus->getListenersForEvent($event) as $listener) {
            $listener->listen($event);
        }
    }
}
