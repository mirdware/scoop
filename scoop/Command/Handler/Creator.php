<?php

namespace Scoop\Command\Handler;

class Creator extends Router
{
    public function __construct(\Scoop\Command\Writer $writer)
    {
        parent::__construct(
            'create new starter artifacts',
            $writer,
            new \Scoop\Command\Bus( array(
                'struct' => 'Scoop\Command\Handler\Creator\Struct'
            )
        ));
    }
}
