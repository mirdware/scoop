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
            'types' => 'Scoop\Command\Handler\Scanner\Type',
            'routes' => 'Scoop\Command\Handler\Scanner\Route'
        )));
    }
}
