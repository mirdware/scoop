<?php

namespace Scoop\Command\Handler;

class Scanner extends Router
{
    public function __construct(\Scoop\Command\Writer $writer)
    {
        parent::__construct(
            'Scan project folders',
            $writer,
            new \Scoop\Command\Bus( array(
            'source' => 'Scoop\Command\Handler\Scanner\Source',
            'routes' => 'Scoop\Command\Handler\Scanner\Route'))
        );
    }
}
