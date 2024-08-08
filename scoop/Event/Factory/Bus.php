<?php

namespace Scoop\Event\Factory;

class Bus
{
    public function create()
    {
        return new \Scoop\Event\Bus(
            \Scoop\Context::getEnvironment()->getConfig('events', array())
        );
    }
}
