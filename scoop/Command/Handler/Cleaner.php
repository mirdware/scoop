<?php

namespace Scoop\Command\Handler;

class Cleaner extends Router
{
    public function __construct(\Scoop\Command\Writer $writer)
    {
        parent::__construct(
            'Clean all view files.',
            $writer,
            new \Scoop\Command\Bus( array(
            'cache' => 'Scoop\Command\Handler\Cleaner\Cache',
            'views' => 'Scoop\Command\Handler\Cleaner\View'))
        );
    }
}
