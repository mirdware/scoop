<?php
namespace Scoop\Event;

class Provider
{
    private $listeners = array();

    public function __construct($providers)
    {
        $this->bind($providers);
    }

    public function getListenersForEvent(object $event): iterable
    {
        $classBase = '\scoop\Event';
        $eventType = get_class($event);
        $listeners = array();
        if (!($event instanceof $classBase)) {
            throw new \UnexpectedValueException($eventType.' not implement '.$classBase);
        }
        if (isset($this->listeners[$eventType])) {
            foreach ($this->listeners[$eventType] as $listener) {
                array_push($listeners, is_callable($listener) ? $listener : \Scoop\Context::inject($listener));
            }
        }
        return $listeners;
    }

    private function bind($providers)
    {
        foreach ($providers as $eventType => $listeners) {
            $eventType = \Scoop\Container\Injector::formatClassName($eventType);
            $this->listeners[$eventType] = $listeners;
        }
    }
}
