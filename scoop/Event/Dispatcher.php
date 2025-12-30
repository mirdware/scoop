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
            try {
                $this->startListener($listener, $event);
                call_user_func($listener, $event);
                $this->finishListener($listener, $event, null);
            } catch (\Throwable $e) {
                $this->finishListener($listener, $event, $e);
                throw $e;
            }
        }
        return $event;
    }

    protected function finishListener($listener, $event, $error)
    {
        $this->dispatch(new ListenerFinished($listener, $event, $error));
    }

    protected function startListener($listener, $event)
    {
        $this->dispatch(new ListenerStarted($listener, $event));
    }
}
