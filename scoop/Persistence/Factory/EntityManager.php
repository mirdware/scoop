<?php

namespace Scoop\Persistence\Factory;

class EntityManager
{
    public function create()
    {
        return new \Scoop\Persistence\Entity\Manager(
            \Scoop\Context::getEnvironment()->getConfig('model', array())
        );
    }
}
