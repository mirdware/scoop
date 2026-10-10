<?php

namespace Scoop\Cache\Factory;

class ItemPool
{
    private $context;

    public function __construct(\Scoop\Context $context)
    {
        $this->context = $context;
    }

    public function create()
    {
        $storagePath = $this->context->getStoragePath('cache');
        $lifetime = $this->context->getConfig('cache.time', 0);
        return new \Scoop\Cache\Item\Pool\File($storagePath, $lifetime);
    }
}
