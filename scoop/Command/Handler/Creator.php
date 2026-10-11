<?php

namespace Scoop\Command\Handler;

class Creator extends Router
{
    public function __construct(\Scoop\Context $context, \Scoop\Command\Writer $writer)
    {
        parent::__construct(
            'create new starter artifacts',
            $writer,
            new \Scoop\Command\Bus(
                $context->getInjector(),
                array('struct' => 'Scoop\Command\Handler\Creator\Struct')
            )
        );
    }
}
