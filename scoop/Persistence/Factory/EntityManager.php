<?php

namespace Scoop\Persistence\Factory;

class EntityManager
{
    private $context;
    private $queryBuilder;

    public function __construct(\Scoop\Context $context, \Scoop\Persistence\Builder $builder)
    {
        $this->context = $context;
        $this->queryBuilder = $builder;
    }

    public function create()
    {
        return new \Scoop\Persistence\Entity\Manager(
            $this->context->getConfig('model.entities', array()),
            $this->context->getConfig('model.values', array()),
            $this->context->getConfig('model.relations', array()),
            $this->context->getConfig('model.types', array()),
            $this->queryBuilder
        );
    }
}
