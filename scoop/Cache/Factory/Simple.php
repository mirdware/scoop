<?php

namespace Scoop\Cache\Factory;

class Simple
{
    private $pool;

    public function __construct(\Scoop\Cache\Item\Pool $pool)
    {
        $this->pool = $pool;
    }

    public function create()
    {
        return new \Scoop\Cache($this->pool);
    }
}
