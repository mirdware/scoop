<?php
namespace Scoop\Event;

class Dispatcher
{
    private $listenerProvider;

    public function __construct($listenerProvider)
    {
        $this->listenerProvider = $listenerProvider;
    }

    public function dispatch(object $event)
    {
        if ($event->isPropagationStopped()) {
            return $event;
        }
        foreach ($this->listenerProvider->getListenersForEvent($event) as $listener) {
            $listener($event);
        }
    }
}
