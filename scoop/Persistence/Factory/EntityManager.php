<?php

namespace Scoop\Persistence\Factory;

class EntityManager
{
    public function create()
    {
        $env = \Scoop\Context::getEnvironment();
        return new \Scoop\Persistence\Entity\Manager(
            $env->getConfig('model.entities', array()),
            $env->getConfig('model.values', array()),
            $env->getConfig('model.relations', array()),
            $env->getConfig('model.types', array())
        );
    }
}
