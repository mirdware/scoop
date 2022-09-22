<?php
namespace Scoop\Storage\Entity;

class Manager
{
    private $map;
    private $collector;

    public function __construct($map)
    {
        $this->map = $map;
        $this->collector = new Collector($map);
    }

    public function __destruct()
    {
        $this->flush();
    }

    public function persist($entity)
    {
        $mapper = $this->getMapper(get_class($entity));
        if (isset($mapper['relations'])) {
            $object = new \ReflectionObject($entity);
            $this->addRelations($entity, $object, $this->filterRelations(
                $mapper['relations'],
                array(Relation::ONE_TO_ONE, Relation::MANY_TO_ONE)
            ));
            $this->collector->add($entity);
            $this->addRelations($entity, $object, $this->filterRelations(
                $mapper['relations'],
                array(Relation::MANY_TO_MANY, Relation::ONE_TO_MANY))
            );
            return;
        }
        $this->collector->add($entity);
    }

    public function remove($entity)
    {
        $this->getMapper(get_class($entity));
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
    }

    public function clean()
    {
        $this->collector = new Collector($this->map);
    }

    private function getMapper($classEntity)
    {
        if (!isset($this->map[$classEntity])) {
            throw new \InvalidArgumentException($classEntity.' not mapper configured');
        }
        return $this->map[$classEntity];
    }

    private function addRelations($entity, $object, $relations)
    {
        foreach ($relations as $name => $relation) {
            $property = $object->getProperty($name);
            $property->setAccessible(true);
            $relationEntity = $property->getValue($entity);
            if (!$relationEntity) continue;
            if (is_array($relationEntity)) {
                foreach ($relationEntity as $e) {
                    $objectRelation = new \ReflectionObject($e);
                    $property = $objectRelation->getProperty($relation[1]);
                    $property->setAccessible(true);
                    $property->setValue($e, $entity);
                    $this->collector->add($e);
                }
            } else {
                $this->collector->add($relationEntity);
            }
        }
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
