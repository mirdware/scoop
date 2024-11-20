<?php

namespace Scoop\Persistence\Entity;

class Manager
{
    private $map;
    private $typeMapper;
    private $mapper;
    private $relations;

    public function __construct($entities, $values, $relations, $types)
    {
        $this->map = compact('entities', 'values', 'relations');
        $this->typeMapper = new TypeMapper($types);
        $this->mapper = new Mapper($entities, $values, $this->typeMapper);
        $this->relations = new Relation($relations, $this->mapper, $this);
        register_shutdown_function(array($this, 'flush'));
    }

    public function save($entity)
    {
        $mapper = $this->getMapper(get_class($entity));
        if (isset($mapper['relations'])) {
            $object = new \ReflectionObject($entity);
            $this->relations->add($entity, $object, $this->filterRelations(
                $mapper['relations'],
                array(Relation::ONE_TO_ONE, Relation::MANY_TO_ONE)
            ));
            $this->mapper->add($entity);
            $this->relations->add($entity, $object, $this->filterRelations(
                $mapper['relations'],
                array(Relation::MANY_TO_MANY, Relation::ONE_TO_MANY)
            ));
            return;
        }
        $this->mapper->add($entity);
    }

    public function remove($entity)
    {
        $mapper = $this->getMapper(get_class($entity));
        if (isset($mapper['relations'])) {
            $this->relations->remove($entity, $mapper['relations']);
        }
        $this->mapper->remove($entity);
    }

    public function search($classEntity)
    {
        $this->getMapper($classEntity);
        return new Query($this->mapper, $classEntity, $this->map);
    }

    public function flush()
    {
        $this->mapper->save();
        $this->relations->save();
    }

    public function clean()
    {
        $this->mapper = new Mapper($this->map['entities'], $this->map['values'], $this->typeMapper);
        $this->relations = new Relation($this->map['relations'], $this->mapper, $this);
    }

    private function getMapper($classEntity)
    {
        if (!isset($this->map['entities'][$classEntity])) {
            throw new \InvalidArgumentException($classEntity . ' not mapper configured');
        }
        return $this->map['entities'][$classEntity];
    }

    private function filterRelations($relations, $types)
    {
        $result = array();
        foreach ($relations as $name => $relation) {
            if (in_array($relation[2], $types)) {
                $result[$name] = $relation;
            }
        }
        return $result;
    }
}
