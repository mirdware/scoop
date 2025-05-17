<?php

namespace Scoop;

class Cache
{
    private $itemPool;

    public function __construct($itemPool)
    {
        $this->itemPool = $itemPool;
    }

    public function get($key, $default = null)
    {
        $item = $this->itemPool->getItem((string) $key);
        return $item->isHit() ? $item->get() : $default;
    }

    public function set($key, $value, $ttl = null)
    {
        $item = $this->itemPool->getItem((string) $key);
        $item->set($value);
        if ($ttl !== null) {
            $item->expiresAfter($ttl);
        }
        return $this->itemPool->save($item);
    }

    public function delete($key)
    {
        return $this->itemPool->deleteItem((string) $key);
    }

    public function clear()
    {
        return $this->itemPool->clear();
    }

    public function getMultiple($keys, $default = null)
    {
        $items = array();
        $psr6Items = $this->itemPool->getItems($this->iterableToArray($keys));
        foreach ($psr6Items as $key => $item) {
            $items[$key] = $item->isHit() ? $item->get() : $default;
        }
        return $items;
    }

    public function setMultiple($values, $ttl = null)
    {
        $value = $this->iterableToArray($values);
        if (empty($value)) return true;
        $success = true;
        foreach ($values as $key => $value) {
            $item = $this->itemPool->getItem((string) $key);
            $item->set($value);
            if ($ttl !== null) {
                $item->expiresAfter($ttl);
            }
            if (!$this->itemPool->save($item)) {
                $success = false;
            }
        }
        return $success;
    }

    public function deleteMultiple($keys)
    {
        return $this->itemPool->deleteItems($this->iterableToArray($keys));
    }

    public function has($key)
    {
        return $this->itemPool->hasItem((string) $key);
    }

    private function iterableToArray($iterable)
    {
        if (is_array($iterable)) {
            return $iterable;
        }
        if ($iterable instanceof \Traversable) {
            return iterator_to_array($iterable, true);
        }
        throw new \InvalidArgumentException('$keys must be an array or Traversable');
    }
}
