<?php

namespace Scoop\Event;

class Bus
{
    private $events = array();
    private $listeners = array();
    private $method;

    public function __construct($providers, $method = 'listen')
    {
        $this->method = $method;
        foreach ($providers as $eventType => $listeners) {
            $eventType = \Scoop\Container\Injector::formatClassName($eventType);
            foreach ($listeners as $listener) {
                if (!method_exists($listener, $method)) {
                    throw new \UnexpectedValueException("$eventType does not implement $method method");
                }
            }
            $this->events[$eventType] = $listeners;
        }
    }

    public function getListenersForEvent($event)
    {
        $eventName = get_class($event);
        if (isset($this->listeners[$eventName])) {
            return $this->listeners[$eventName];
        }
        $this->listeners[$eventName] = array();
        $eventClass = $eventName;
        do {
            if (isset($this->events[$eventClass])) {
                foreach ($this->events[$eventClass] as $listener) {
                    array_push($this->listeners[$eventName], array(\Scoop\Context::inject($listener), $this->method));
                }
            }
            $eventClass = get_parent_class($eventClass);
        } while ($eventClass !== false);
        return $this->listeners[$eventName];
    }

    public function has($event)
    {
        return isset($this->events[get_class($event)]);
    }
}
