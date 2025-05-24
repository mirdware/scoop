<?php

namespace Scoop\Command\Handler\Cache;

class Pruner
{
    private $cache;
    private $writer;

    public function __construct(
        \Scoop\Command\Writer $writer,
        \Scoop\Cache\Item\Pool $cache
    ) {
        $this->writer = $writer;
        $this->cache = $cache;
    }

    public function execute()
    {
        $this->cache->prune();
        $this->writer->write('Cache pruned successfully.');
    }

    public function help()
    {
        $this->writer->write('Removes expired items from the cache.');
    }
}
