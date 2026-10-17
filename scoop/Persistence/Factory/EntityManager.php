<?php

namespace Scoop\Persistence\Factory;

class EntityManager
{
    private $context;
    private $lifecycle;
    private $queryBuilder;
    private $dispatcher;

    public function __construct(
        \Scoop\Context $context,
        \Scoop\Bootstrap\Lifecycle $lifecycle,
        \Scoop\Persistence\Builder $builder,
        \Scoop\Event\Dispatcher $dispatcher
    )
    {
        $this->context = $context;
        $this->lifecycle = $lifecycle;
        $this->queryBuilder = $builder;
        $this->dispatcher = $dispatcher;
    }

    public function create()
    {
        return new \Scoop\Persistence\Entity\Manager(
            $this->lifecycle,
            $this->dispatcher,
            $this->queryBuilder,
            $this->context->getConfig('model.entities', array()),
            $this->context->getConfig('model.values', array()),
            $this->context->getConfig('model.relations', array()),
            $this->context->getConfig('model.types', array())
        );
    }
}
