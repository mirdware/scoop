<?php

namespace Scoop\Cache\Item\Pool;

class Memory extends \Scoop\Cache\Item\Pool
{
    private $items;
    private $cleanupInterval;
    private $cleanupLimit;
    private $nextCleanup = 0;

    public function __construct($defaultLifetime, $cleanupInterval, $cleanupLimit)
    {
        parent::__construct($defaultLifetime);
        $this->items = array();
        $this->cleanupInterval = (int) $cleanupInterval;
        $this->cleanupLimit = (int) $cleanupLimit;
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
        $this->maintain();
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
        $this->maintain();
        $this->items[$item->getKey()] = clone $item;
        return true;
    }

    private function maintain()
    {
        if ($this->cleanupInterval < 1 || time() < $this->nextCleanup) {
            return;
        }
        $this->nextCleanup = time() + $this->cleanupInterval;
        $remaining = min($this->cleanupLimit, count($this->items));
        while ($remaining-- > 0) {
            if (key($this->items) === null) {
                reset($this->items);
            }
            $key = key($this->items);
            $item = current($this->items);
            next($this->items);
            if (!$item->isHit()) {
                unset($this->items[$key]);
            }
        }
    }
}
