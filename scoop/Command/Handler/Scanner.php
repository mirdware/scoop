<?php

namespace Scoop\Command\Handler;

class Scanner extends Router
{
    public function __construct(\Scoop\Context $context, \Scoop\Command\Writer $writer)
    {
        parent::__construct(
            'Scan project folders',
            $writer,
            new \Scoop\Command\Bus(
                $context->getInjector(),
                array(
                    'source' => 'Scoop\Command\Handler\Scanner\Source',
                    'routes' => 'Scoop\Command\Handler\Scanner\Route'
                )
            )
        );
    }
}
