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
        $storagePath = $this->environment->getConfig('storage', 'app/storage');
        $lifetime = $this->environment->getConfig('cache', 0);
        return new \Scoop\Cache\Item\Pool\File($storagePath . '/cache', $lifetime);
    }
}
