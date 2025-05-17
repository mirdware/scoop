<?php

namespace Scoop\Cache\Item;

class Memory extends \Scoop\Cache\Item\Pool
{
    private $items;

    public function __construct($defaultLifetime = 0)
    {
        parent::__construct($defaultLifetime);
        $this->items = array();
    }

    public function prune()
    {
        $prunedAnything = false;
        foreach ($this->items as $key => $item) {
            if (!$item->isHit()) {
                $prunedAnything = true;
                unset($this->items[$key]);
            }
        }
        return $prunedAnything;
    }

    protected function fetch($key)
    {
        if (isset($this->items[$key])) {
            return clone $this->items[$key];
        }
        return null;
    }

    protected function remove($key)
    {
        unset($this->items[$key]);
        return true;
    }

    protected function removeAll()
    {
        $this->items = array();
        return true;
    }

    protected function add(\Scoop\Cache\Item $item)
    {
        $this->items[$item->getKey()] = clone $item;
        return true;
    }
}
