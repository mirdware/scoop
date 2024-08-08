<?php

namespace Scoop\Event;

class Dispatcher
{
    private $bus;
    private $lastResponse;

    public function __construct(\Scoop\Event\Bus $bus)
    {
        $this->bus = $bus;
    }

    #[\ReturnTypeWillChange]
    public function dispatch($event)
    {
        $listeners = $this->bus->getListenersForEvent($event);
        if (method_exists($event, 'isPropagationStopped')) {
            $this->walkWithStop($listeners, $event);
        } else {
            $this->walkWithoutStop($listeners, $event);
        }
    }

    public function getLastResponse()
    {
        return $this->lastResponse;
    }

    private function walkWithStop($listeners, $event)
    {
        foreach ($listeners as $listener) {
            if ($event->isPropagationStopped()) break;
            $this->lastResponse = call_user_func($listener, $event);
        }
    }

    private function walkWithoutStop($listeners, $event)
    {
        foreach ($listeners as $listener) {
            $this->lastResponse = call_user_func($listener, $event);
        }
    }
}
