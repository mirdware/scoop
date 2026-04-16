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
            $this->events[$eventType] = array();
            foreach ($listeners as $listener => $middlewares) {
                if (is_numeric($listener) && is_string($middlewares)) {
                    $listener = $middlewares;
                }
                $listener = \Scoop\Container\Injector::formatClassName($listener);
                if (!method_exists($listener, $method)) {
                    throw new \UnexpectedValueException("Listener $listener does not implement $method method");
                }
                if (!is_array($middlewares)) {
                    $callable = array($listener, 'getMiddlewares');
                    $middlewares = is_callable($callable) ? call_user_func($callable) : array();
                }
                $this->events[$eventType][$listener] = $middlewares;
            }
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
                foreach ($this->events[$eventClass] as $listenerClass => $middlewares) {
                    $this->listeners[$eventName][] = new \Scoop\Event\Listener\Wrapper(
                        $listenerClass,
                        $this->method,
                        $middlewares
                    );
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
