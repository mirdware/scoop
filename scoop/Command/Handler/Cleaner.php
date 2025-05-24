<?php

namespace Scoop\Command\Handler;

class Cleaner extends Router
{
    public function __construct(\Scoop\Command\Writer $writer)
    {
        parent::__construct(
            'Manages application cache: prune expired items or fully clear all cached data.',
            $writer,
            new \Scoop\Command\Bus( array(
            'cache' => 'Scoop\Command\Handler\Cleaner\Cache',
            'views' => 'Scoop\Command\Handler\Cleaner\View'))
        );
    }
}
