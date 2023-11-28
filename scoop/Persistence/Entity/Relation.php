<?php

namespace Scoop\Persistence\Entity;

class Relation
{
    const ONE_TO_ONE = 1;
    const ONE_TO_MANY = 2;
    const MANY_TO_ONE = 3;
    const MANY_TO_MANY = 4;
    private $collector;
    private $many;
    private $relationMap;

    public function __construct($map, $collector)
    {
        $this->relationMap = $map;
        $this->collector = $collector;
        $this->many = array();
    }

    public function add($entity, $object, $relations)
    {
        foreach ($relations as $name => $relation) {
            $property = $object->getProperty($name);
            $property->setAccessible(true);
            $relationEntity = $property->getValue($entity);
            list($relationName, $mapperKey) = $this->getPropertyRelation($relation);
            if (!$relationEntity) {
                continue;
            }
            if (is_array($relationEntity)) {
                foreach ($relationEntity as $e) {
                    $objectRelation = new \ReflectionObject($e);
                    $property = $objectRelation->getProperty($relationName);
                    $property->setAccessible(true);
                    $property->setValue($e, $entity);
                    $this->collector->add($e);
                    if (!is_null($mapperKey)) {
                        $this->many[$mapperKey][] = array($entity, $e);
                    }
                }
            } elseif (is_object($relationEntity)) {
                $objectRelation = new \ReflectionObject($relationEntity);
                $property = $objectRelation->getProperty($relationName);
                $property->setAccessible(true);
                $value = $property->getValue($relationEntity);
                if (is_array($value)) {
                    if (!in_array($entity, $value)) {
                        array_push($value, $entity);
                    }
                } else {
                    $value = $entity;
                }
                $property->setValue($relationEntity, $value);
                $this->collector->add($relationEntity);
            }
        }
    }

    public function remove($entity, $relations)
    {
        $object = new \ReflectionObject($entity);
        foreach ($relations as $name => $relation) {
            $property = $object->getProperty($name);
            $property->setAccessible(true);
            $relationEntity = $property->getValue($entity);
            if (!$relationEntity) {
                continue;
            }
            if (is_array($relationEntity)) {
                foreach ($relationEntity as $e) {
                    $this->collector->remove($e);
                }
            } elseif (is_object($relationEntity)) {
                $objectRelation = new \ReflectionObject($relationEntity);
                $relationName = $this->getPropertyRelation($relation)[0];
                $property = $objectRelation->getProperty($relationName);
                $property->setAccessible(true);
                $value = $property->getValue($relationEntity);
                if (is_array($value)) {
                    $index = array_search($entity, $value);
                    if ($index) {
                        array_splice($value, $index, 1);
                    }
                } else {
                    $this->collector->remove($relationEntity);
                    $value = null;
                }
                $property->setValue($relationEntity, $value);
            }
        }
    }

    public function save()
    {
        foreach ($this->many as $key => $relation) {
            $sqo = new \Scoop\Persistence\SQO($this->relationMap[$key]['table']);
            $fields = array();
            $properties = array();
            foreach ($this->relationMap[$key]['columns'] as $name => $column) {
                if (isset($column['foreign'])) {
                    $fields[] = $name;
                    $properties[] = $column['foreign'];
                }
            }
            $idNames = array_map(array($this->collector, 'getIdName'), $properties);
            $create = $sqo->create($fields);
            foreach ($relation as $entities) {
                $id = array();
                foreach ($entities as $entity) {
                    $object = new \ReflectionObject($entity);
                    $name = $object->getName();
                    $i = array_search($name, $properties);
                    $prop = $object->getProperty($idNames[$i]);
                    $prop->setAccessible(true);
                    $id[$i] = $prop->getValue($entity);
                }
                ksort($id);
                $create->create($id);
            }
            $create->run();
        }
        $this->many = array();
    }

    private function getPropertyRelation($relation)
    {
        $relationProperty = array($relation[1], null);
        if ($relation[2] === self::MANY_TO_MANY) {
            $relationProperty = explode(':', $relation[1]);
            if (!isset($relationProperty[1])) {
                throw new \UnexpectedValueException('Property ' . $relation[1] . ' malphormed for MANY TO MANY relation');
            }
            if (!isset($this->relationMap[$relationProperty[1]])) {
                throw new \UnexpectedValueException('Mapper for relation ' . $relation[1] . ' not exist');
            }
        }
        return $relationProperty;
    }
}
