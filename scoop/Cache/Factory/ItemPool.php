<?php

namespace Scoop\Cache\Factory;

class ItemPool
{
    private $environment;

    public function __construct(\Scoop\Bootstrap\Environment $environment)
    {
        $this->environment = $environment;
    }

    public function create()
    {
        $storagePath = $this->environment->getConfig('cache.storage', 'app/storage/cache');
        $lifetime = $this->environment->getConfig('cache.time', 0);
        return new \Scoop\Cache\Item\Pool\File($storagePath, $lifetime);
    }
}
