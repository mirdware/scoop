<?php

namespace Scoop\Persistence\Entity\Mapper;

class Identity implements \IteratorAggregate
{
    private $entities;
    private $attached;
    private $persisted;
    private $removed;

    public function __construct()
    {
        $this->entities = new \SplObjectStorage();
        $this->attached = array();
        $this->persisted = array();
        $this->removed = array();
    }

    #[\ReturnTypeWillChange]
    public function getIterator()
    {
        return $this->entities;
    }

    public function contains($entity)
    {
        return isset($this->entities[$entity]);
    }

    public function getKey($entity)
    {
        return $this->entities[$entity];
    }

    public function attach($key, $entity)
    {
        if (isset($this->attached[$key])) {
            unset($this->entities[$entity]);
        }
        if (isset($this->persisted[$key])) {
            $this->persisted[$key]['entity'] = $entity;
        }
        if (isset($this->removed[$key])) {
            $this->removed[$key] = $entity;
        }
        $this->entities[$entity] = $key;
        $this->attached[$key] = $entity;
    }

    public function getAttachedEntity($key)
    {
        return isset($this->attached[$key]) ? $this->attached[$key] : null;
    }

    public function markRemoved($key, $entity)
    {
        $this->removed[$key] = $entity;
    }

    public function unmarkRemoved($key)
    {
        unset($this->removed[$key]);
    }

    public function isRemoved($key)
    {
        return isset($this->removed[$key]);
    }

    public function isPersisted($key)
    {
        return isset($this->persisted[$key]);
    }

    public function getPersistedEntity($key)
    {
        return $this->persisted[$key]['entity'];
    }

    public function setPersisted($key, $entity, $fields)
    {
        $this->persisted[$key] = array('entity' => $entity, 'fields' => $fields);
    }

    public function &getPersistedFields($key, $className)
    {
        if (!isset($this->persisted[$key]['fields'][$className])) {
            $this->persisted[$key]['fields'][$className] = array();
        }
        return $this->persisted[$key]['fields'][$className];
    }

    public function reassignKey($entity, $newKey)
    {
        $this->entities[$entity] = $newKey;
    }

    public function detach($entity)
    {
        if ($this->contains($entity)) {
            $key = $this->entities[$entity];
            unset(
                $this->entities[$entity],
                $this->attached[$key],
                $this->removed[$key]
            );
        }
    }

    public function purgeRemoved()
    {
        foreach ($this->removed as $key => $entity) {
            unset($this->persisted[$key], $this->entities[$entity], $this->attached[$key]);
        }
        $this->removed = array();
    }
}
