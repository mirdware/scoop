<?php

namespace Scoop\Storage\Entity;

class Manager
{
    private $map;
    private $collector;
    private $relations;

    public function __construct($map)
    {
        $this->map = $map;
        $this->collector = new Collector($map);
        $this->relations = new Relation($map, $this->collector);
    }

    public function __destruct()
    {
        $this->flush();
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
            $this->collector->add($entity);
            $this->relations->add($entity, $object, $this->filterRelations(
                $mapper['relations'],
                array(Relation::MANY_TO_MANY, Relation::ONE_TO_MANY)
            ));
            return;
        }
        $this->collector->add($entity);
    }

    public function remove($entity)
    {
        $mapper = $this->getMapper(get_class($entity));
        if (isset($mapper['relations'])) {
            $this->relations->remove($entity, $mapper['relations']);
        }
        $this->collector->remove($entity);
    }

    public function find($classEntity)
    {
        $this->getMapper($classEntity);
        return new Query($this->collector, $classEntity, $this->map);
    }

    public function flush()
    {
        $this->collector->save();
        $this->relations->save();
    }

    public function clean()
    {
        $this->collector = new Collector($this->map);
        $this->relations = new Relation($this->map, $this->collector);
    }

    private function getMapper($classEntity)
    {
        if (!isset($this->map[$classEntity])) {
            throw new \InvalidArgumentException($classEntity . ' not mapper configured');
        }
        return $this->map[$classEntity];
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
