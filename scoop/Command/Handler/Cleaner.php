<?php

namespace Scoop\Command\Handler;

class Cleaner extends Router
{
    public function __construct(\Scoop\Context $context, \Scoop\Command\Writer $writer)
    {
        parent::__construct(
            'Clean all view files.',
            $writer,
            new \Scoop\Command\Bus(
                $context->getInjector(),
                array(
                    'cache' => 'Scoop\Command\Handler\Cleaner\Cache',
                    'views' => 'Scoop\Command\Handler\Cleaner\View'
                )
            )
        );
    }
}
