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

    abstract protected function removeAll();

    abstract protected function fetch($key);

    abstract protected function remove($key);

    abstract protected function add(\Scoop\Cache\Item $item);

    abstract public function prune();

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
        $expiration = new \DateTime();
        $expiration->modify("+{$this->defaultLifetime} seconds");
        return new \Scoop\Cache\Item($key, $expiration);
    }

    public function getItems($keys = array())
    {
        if (empty($keys)) {
            return array();
        }
        $results = array();
        foreach ($keys as $key) {
            $keyString = (string) $key;
            $results[$keyString] = $this->getItem($keyString);
        }
        return $results;
    }

    public function hasItem($key)
    {
        try {
            $item = $this->getItem($key);
            return $item->isHit();
        } catch (\InvalidArgumentException $e) {
            return false;
        }
    }

    public function clear()
    {
        $this->deferredItems = array();
        return $this->removeAll();
    }

    public function deleteItem($key)
    {
        unset($this->deferredItems[$key]);
        return $this->remove($key);
    }

    public function deleteItems($keys)
    {
        $allSucceeded = true;
        foreach ($keys as $key) {
            if (!$this->deleteItem((string) $key)) {
                $allSucceeded = false;
            }
        }
        return $allSucceeded;
    }

    public function save($item)
    {
        $key = $item->getKey();
        $success = false;
        if ($item->isHit()) {
            $expirationToStore = null;
            if ($item instanceof \Scoop\Cache\Item) {
                $expirationToStore = $item->getExpiration();
            } elseif ($this->defaultLifetime > 0) {
                $expirationToStore = new \DateTime();
                $expirationToStore->modify("+{$this->defaultLifetime} seconds");
            }
            $success = $this->add(new \Scoop\Cache\Item(
                $key,
                $expirationToStore,
                $item->get(),
                true
            ));
        } else {
            $success = $this->remove($key);
        }
        unset($this->deferredItems[$key]);
        return $success;
    }

    public function saveDeferred($item)
    {
        $key = $item->getKey();
        if ($item->isHit()) {
            $this->deferredItems[$key] = clone $item;
        } else {
            $this->clear();
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
}
