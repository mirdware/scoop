<?php

namespace Scoop\Log\Factory;

class Logger
{
    private $context;

    public function __construct(\Scoop\Bootstrap\Environment $context)
    {
        $this->context = $context;
    }

    public function create()
    {
        return new \Scoop\Log\Logger(
            new \Scoop\Log\Factory\Handler(
                $this->context->getInjector(),
                $this->context->getConfig('log', array())
            )
        );
    }
}
