<?php

namespace Scoop\Log\Factory;

class Logger
{
    private $context;
    private $lifecycle;

    public function __construct(\Scoop\Context $context, \Scoop\Bootstrap\Lifecycle $lifecycle)
    {
        $this->context = $context;
        $this->lifecycle = $lifecycle;
    }

    public function create()
    {
        return new \Scoop\Log\Logger(
            $this->lifecycle,
            new \Scoop\Log\Factory\Handler(
                $this->context->getInjector(),
                $this->context->getConfig('log', array())
            )
        );
    }
}
