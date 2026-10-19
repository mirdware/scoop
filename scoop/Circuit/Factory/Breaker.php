<?php

namespace Scoop\Circuit\Factory;

class Breaker
{
    private $context;
    private $clock;

    public function __construct(\Scoop\Context $context, \Scoop\Clock $clock)
    {
        $this->context = $context;
        $this->clock = $clock;
    }

    public function createInMemory()
    {
        return new \Scoop\Circuit\Breaker\Memory(
            $this->clock,
            $this->context->getConfig('circuit', array())
        );
    }

    public function createInFile()
    {
        return new \Scoop\Circuit\Breaker\File(
            $this->clock,
            $this->context->getStoragePath('circuit'),
            $this->context->getConfig('circuit', array())
        );
    }

    public function createInApcu()
    {
        return new \Scoop\Circuit\Breaker\APCu(
            $this->clock,
            $this->context->getConfig('circuit', array())
        );
    }
}
