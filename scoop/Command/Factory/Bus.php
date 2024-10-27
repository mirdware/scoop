<?php

namespace Scoop\Command\Factory;

class Bus
{
    public function create()
    {
        return new \Scoop\Command\Bus(

            \Scoop\Context::getEnvironment()->getConfig('commands', array()) + array(
                'new' => 'Scoop\Command\Handler\Creator',
                'scan' => 'Scoop\Command\Handler\Scanner',
                'dbup' => 'Scoop\Command\Handler\Structure'
            )
        );
    }
}
