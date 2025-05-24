<?php

namespace Scoop\Command\Handler;

class Cache extends Router
{
    public function __construct(\Scoop\Command\Writer $writer)
    {
        parent::__construct(
            'Manages application cache: prune expired items or fully clear all cached data.',
            $writer,
            new \Scoop\Command\Bus( array(
            'prune' => 'Scoop\Command\Handler\Cache\Pruner',
            'clear' => 'Scoop\Command\Handler\Cache\Cleaner'))
        );
    }
}
