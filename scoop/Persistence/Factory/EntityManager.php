<?php

namespace Scoop\Persistence\Factory;

class EntityManager
{
    private $context;

    public function __construct(\Scoop\Bootstrap\Environment $context)
    {
        $this->context = $context;
    }

    public function create()
    {
        return new \Scoop\Persistence\Entity\Manager(
            $this->context->getConfig('model.entities', array()),
            $this->context->getConfig('model.values', array()),
            $this->context->getConfig('model.relations', array()),
            $this->context->getConfig('model.types', array()),
            new \Scoop\Persistence\Builder()
        );
    }
}
