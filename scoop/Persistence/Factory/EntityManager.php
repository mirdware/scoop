<?php

namespace Scoop\Persistence\Factory;

class EntityManager
{
    private $environment;

    public function __construct(\Scoop\Bootstrap\Environment $environment)
    {
        $this->environment = $environment;
    }

    public function create()
    {
        return new \Scoop\Persistence\Entity\Manager(
            $this->environment->getConfig('model.entities', array()),
            $this->environment->getConfig('model.values', array()),
            $this->environment->getConfig('model.relations', array()),
            $this->environment->getConfig('model.types', array())
        );
    }
}
