<?php

namespace Scoop\Cache\Factory;

class ItemPool
{
    private $context;

    public function __construct(\Scoop\Context $context)
    {
        $this->context = $context;
    }

    public function createInFile()
    {
        return new \Scoop\Cache\Item\Pool\File(
            $this->context->getStoragePath('cache'),
            $this->context->getConfig('cache.time', 0),
            $this->context->getConfig('cache.cleanup.interval', 60),
            $this->context->getConfig('cache.cleanup.limit', 100)
        );
    }

    public function createInMemory()
    {
        return new \Scoop\Cache\Item\Pool\Memory(
            $this->context->getConfig('cache.time', 0),
            $this->context->getConfig('cache.cleanup.interval', 60),
            $this->context->getConfig('cache.cleanup.limit', 100)
        );
    }

    public function createInApcu()
    {
        $namespace = $this->context->getConfig(
            'cache.namespace',
            realpath($this->context->getStoragePath('cache'))
        );
        return new \Scoop\Cache\Item\Pool\Apcu(
            $namespace,
            $this->context->getConfig('cache.time', 0)
        );
    }
}
