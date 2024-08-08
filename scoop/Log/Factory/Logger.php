<?php

namespace Scoop\Log\Factory;

class Logger
{
    public function create()
    {
        $factory = new \Scoop\Log\Factory\Handler(
            \Scoop\Context::getEnvironment()->getConfig('log', array())
        );
        return new \Scoop\Log\Logger($factory);
    }
}
