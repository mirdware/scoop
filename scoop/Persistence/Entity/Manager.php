<?php

namespace Scoop\Persistence\Entity;

class Manager
{
    private $map;
    private $typeMapper;
    private $mapper;
    private $relations;
    private $accessor;
    private $hasProperties;

    public function __construct($entities, $values, $relations, $types)
    {
        $this->map = compact('entities', 'values', 'relations');
        $this->accessor = new Accessor();
        $this->typeMapper = new Mapper\Type($types);
        $this->mapper = new Mapper($entities, $values, $this->typeMapper, $this->accessor);
        $this->relations = new Relation($relations, $this->mapper, $this, $this->accessor);
        $this->hasProperties = array();
        register_shutdown_function(array($this, 'flush'));
    }

    public function save($entity)
    {
        $mapper = $this->getMapper(get_class($entity));
        if (isset($mapper['relations'])) {
            $this->relations->add($entity, $this->filterRelations(
                $mapper['relations'],
                array(Relation::ONE_TO_ONE, Relation::MANY_TO_ONE)
            ));
            $this->mapper->add($entity);
            $this->relations->add($entity, $this->filterRelations(
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
        return new Query($this->mapper, $classEntity, $this->map, $this->accessor);
    }

    public function flush()
    {
        $this->mapper->save();
        $this->relations->save();
    }

    public function clean()
    {
        $this->mapper = new Mapper($this->map['entities'], $this->map['values'], $this->typeMapper, $this->accessor);
        $this->relations = new Relation($this->map['relations'], $this->mapper, $this, $this->accessor);
    }

    private function getMapper($classEntity)
    {
        if (!isset($this->hasProperties[$classEntity])) {
            $currentClass = $classEntity;
            while ($currentClass) {
                if (!isset($this->map['entities'][$currentClass])) {
                    throw new \InvalidArgumentException("$currentClass not mapper configured");
                }
                $mapper = $this->map['entities'][$currentClass];
                $idName = isset($mapper['id']) ? $mapper['id'] : 'id';
                foreach ($mapper['properties'] as $propName => $propDef) {
                    if (!$this->accessor->getDeclaringClass($currentClass, $propName)) {
                        throw new \UnexpectedValueException(
                            "Property $propName mapped for $currentClass does not exist"
                        );
                    }
                    if ($propName === $idName) {
                        $idName = null;
                    }
                }
                $currentClass = get_parent_class($currentClass);
            }
            if (isset($idName)) {
                throw new \UnexpectedValueException(
                    "Property $idName mapped for $classEntity does not exist"
                );
            }
            $this->hasProperties[$classEntity] = true;
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
