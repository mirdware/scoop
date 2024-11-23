<?php

namespace Scoop\Command\Factory;

class Bus
{
    private $environment;

    public function __construct(\Scoop\Bootstrap\Environment $environment)
    {
        $this->environment = $environment;
    }

    public function create()
    {
        return new \Scoop\Command\Bus(
            $this->environment->getConfig('commands', array()) + array(
                'new' => 'Scoop\Command\Handler\Creator',
                'scan' => 'Scoop\Command\Handler\Scanner',
                'dbup' => 'Scoop\Command\Handler\Structure'
            )
        );
    }
}
