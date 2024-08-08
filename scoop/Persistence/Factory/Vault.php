<?php

namespace Scoop\Persistence\Factory;

class Vault
{
    public function create()
    {
        return new \Scoop\Persistence\Vault(
            \Scoop\Context::getEnvironment()->getConfig('vault', array())
        );
    }
}
