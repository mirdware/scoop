<?php

namespace Scoop\Event\Factory;

class Bus
{
    private $environment;

    public function __construct(\Scoop\Bootstrap\Environment $environment)
    {
        $this->environment = $environment;
    }

    public function create()
    {
        return new \Scoop\Event\Bus(
            $this->environment->getConfig('events', array())
        );
    }
}
