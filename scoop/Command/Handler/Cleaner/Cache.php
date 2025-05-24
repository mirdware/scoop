<?php

namespace Scoop\Command\Handler\Cleaner;

class Cache
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

    public function execute($command)
    {
        if ($command->hasFlag('f')) {
            $this->cache->clear();
            return $this->writer->write('Cache cleaned <success:successfully!>.');
        }
        $this->cache->prune();
        $this->writer->write('Cache pruned <success:successfully!>.');
    }

    public function help()
    {
        $this->writer->write(
            'Removes expired items from the cache.',
            '',
            'Flags:',
            '  -f => Force cleaning of all caches'
        );
    }
}
