<?php

namespace Scoop\Command\Factory;

class Bus
{
    private $context;

    public function __construct(\Scoop\Bootstrap\Environment $context)
    {
        $this->context = $context;
    }

    public function create()
    {
        return new \Scoop\Command\Bus(
            $this->context->getInjector(),
            $this->context->getConfig('ice.commands', array()) + array(
                'new' => 'Scoop\Command\Handler\Creator',
                'scan' => 'Scoop\Command\Handler\Scanner',
                'dbup' => 'Scoop\Command\Handler\Structure',
                'preload' => 'Scoop\Command\Handler\PreLoader',
                'clean' => 'Scoop\Command\Handler\Cleaner',
                'check' => 'Scoop\Command\Handler\Checker'
            )
        );
    }
}
