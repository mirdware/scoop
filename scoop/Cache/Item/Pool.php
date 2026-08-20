<?php

namespace Scoop\Cache\Item;

abstract class Pool
{
    private $deferredItems;
    private $defaultLifetime;

    public function __construct($defaultLifetime = 0)
    {
        $this->deferredItems = array();
        $this->defaultLifetime = (int) $defaultLifetime;
    }

    public function getItem($key)
    {
        if (isset($this->deferredItems[$key])) {
            return clone $this->deferredItems[$key];
        }
        $persistedItem = $this->fetch($key);
        if ($persistedItem) {
            if ($persistedItem->isHit()) {
                return $persistedItem;
            }
            $this->remove($key);
        }
        if ($this->defaultLifetime > 0) {
            $expiration = new \DateTime();
            $expiration->modify("+{$this->defaultLifetime} seconds");
        } else {
            $expiration = null;
        }
        return new \Scoop\Cache\Item($key, $expiration);
    }

    public function getItems($keys = array())
    {
        if (empty($keys)) {
            return array();
        }
        $results = array();
        foreach ($keys as $key) {
            $results[$key] = $this->getItem($key);
        }
        return $results;
    }

    public function hasItem($key)
    {
        $item = $this->getItem($key);
        return $item->isHit();
    }

    public function clear()
    {
        $this->deferredItems = array();
        return $this->removeAll();
    }

    public function deleteItem($key)
    {
        $this->getItem($key);
        unset($this->deferredItems[$key]);
        return $this->remove($key);
    }

    public function deleteItems($keys)
    {
        $allSucceeded = true;
        foreach ($keys as $key) {
            if (!$this->deleteItem($key)) {
                $allSucceeded = false;
            }
        }
        return $allSucceeded;
    }

    public function save(\Scoop\Cache\Item $item)
    {
        $key = $item->getKey();
        $state = $item->getState();
        if (!$item->isHit() && !$state->hasPendingChanges) {
            $success = $this->remove($key);
            unset($this->deferredItems[$key]);
            return $success;
        }
        $success = $this->add(new \Scoop\Cache\Item($key, $state->expiration, $state->value, true));
        unset($this->deferredItems[$key]);
        return $success;
    }

    public function saveDeferred(\Scoop\Cache\Item $item)
    {
        $key = $item->getKey();
        $state = $item->getState();
        if ($item->isHit() || $state->hasPendingChanges) {
            $this->deferredItems[$key] = clone $item;
        } else {
            unset($this->deferredItems[$key]);
            $this->remove($key);
        }
        return true;
    }

    public function commit()
    {
        $allSucceeded = true;
        foreach ($this->deferredItems as $item) {
            if (!$this->save($item)) {
                $allSucceeded = false;
            }
        }
        $this->deferredItems = array();
        return $allSucceeded;
    }

    public function __destruct()
    {
        if (!empty($this->deferredItems)) {
            $this->commit();
        }
    }

    abstract protected function removeAll();

    abstract protected function fetch($key);

    abstract protected function remove($key);

    abstract protected function add(\Scoop\Cache\Item $item);

    abstract public function prune();
}
