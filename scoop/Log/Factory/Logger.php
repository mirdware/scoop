<?php

namespace Scoop\Log\Factory;

class Logger
{
    private $environment;

    public function __construct(\Scoop\Bootstrap\Environment $environment)
    {
        $this->environment = $environment;
    }

    public function create()
    {
        return new \Scoop\Log\Logger(
            new \Scoop\Log\Factory\Handler(
                $this->environment->getConfig('log', array()),
                $this->environment->getConfig('storage', 'app/storage/')
                . 'logs/' . $this->environment->getConfig('app.name')
                . '-' . date('Y-m-d') . '.log'
            )
        );
    }
}