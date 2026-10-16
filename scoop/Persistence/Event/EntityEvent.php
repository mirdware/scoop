<?php

namespace Scoop\Persistence\Event;

abstract class EntityEvent
{
    private $entity;
    private $time;

    public function __construct($entity)
    {
        $this->time = new \DateTime();
        $this->entity = $entity;
    }

    public function getEntity()
    {
        return $this->entity;
    }

    public function getTime()
    {
        return $this->time;
    }
}
