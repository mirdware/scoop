<?php
namespace Scoop\Event;

class Bus
{
    private $listeners = array();

    public function __construct($providers)
    {
        $this->bind($providers);
    }

    public function getListenersForEvent($event)
    {
        $eventClass = get_class($event);
        $listeners = array();
        do {
            if (isset($this->listeners[$eventClass])) {
                foreach ($this->listeners[$eventClass] as $listener) {
                    array_push($listeners, \Scoop\Context::inject($listener));
                }
            }
            $eventClass = get_parent_class($eventClass);
        } while ($eventClass !== \Scoop\Event::class);
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
