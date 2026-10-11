<?php

namespace Scoop\Event\Factory;

class Bus
{
    private $context;

    public function __construct(\Scoop\Context $context)
    {
        $this->context = $context;
    }

    public function create()
    {
        return new \Scoop\Event\Bus(
            $this->context->getInjector(),
            $this->context->getConfig('events', array())
        );
    }
}
