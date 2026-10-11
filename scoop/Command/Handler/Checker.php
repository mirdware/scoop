<?php

namespace Scoop\Command\Handler;

class Checker extends Router
{
    public function __construct(\Scoop\Context $context, \Scoop\Command\Writer $writer)
    {
        parent::__construct(
            'check diferent status',
            $writer,
            new \Scoop\Command\Bus(
                $context->getInjector(),
                array('health' => 'Scoop\Command\Handler\Checker\Health')
            )
        );
    }
}
