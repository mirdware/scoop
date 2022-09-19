<?php
namespace Scoop\Storage\Entity;

class Collector
{
    private $entities = array();

    public function add($entity, $mapper)
    {
        $this->entities[$mapper['id']] = $entity;
    }

    public function remove($entity, $mapper)
    {
        unset($this->entities[$mapper['id']]);
    }

    public function save()
    {

    }
}
