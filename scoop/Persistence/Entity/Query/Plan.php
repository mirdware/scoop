<?php

namespace Scoop\Persistence\Entity\Query;

class Plan
{
    private $fields;
    private $joins;
    private $discriminator;

    public function __construct($root, $map, $mapper, $builder)
    {
        $fieldResolver = new \Scoop\Persistence\Entity\Resolver\Field($map, $mapper, array(), array());
        $fieldResolver->addFields($root, 'r', false);
        $this->discriminator = new \Scoop\Persistence\Entity\Mapper\Discriminator($root, $map['entities'], $builder);
        $discriminatorColumn = $this->discriminator->getColumn();
        if ($discriminatorColumn) {
            $fieldResolver->addRawField($discriminatorColumn, "r.$discriminatorColumn");
        }
        $this->fields = $fieldResolver->getFields();
        $this->joins = $fieldResolver->getJoins();
    }

    public function createFieldResolver($map, $mapper)
    {
        return new \Scoop\Persistence\Entity\Resolver\Field($map, $mapper, $this->fields, $this->joins);
    }

    public function getDiscriminator()
    {
        return $this->discriminator;
    }
}
