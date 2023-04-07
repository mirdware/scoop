<?php

namespace Scoop\Storage\Entity;

class Relation extends Mapper
{
    const ONE_TO_ONE = 1;
    const ONE_TO_MANY = 2;
    const MANY_TO_ONE = 3;
    const MANY_TO_MANY = 4;
    private $collector;
    private $many;

    public function __construct($map, $collector)
    {
        parent::__construct($map);
        $this->collector = $collector;
        $this->many = array();
    }
    
    public function add($entity, $object, $relations)
    {
        foreach ($relations as $name => $relation) {
            $property = $object->getProperty($name);
            $property->setAccessible(true);
            $relationEntity = $property->getValue($entity);
            if (!$relationEntity) {
                continue;
            }
            if (is_array($relationEntity)) {
                $mapperKey = null;
                if ($relation[2] === self::MANY_TO_MANY) {
                    $classEntity = $object->getName();
                    $mapperKey = $classEntity . ':' . $relation[0];
                    $isFirstThis = true;
                    if (!isset($this->map[$mapperKey])) {
                        $mapperKey = $relation[0] . ':' . $classEntity;
                        $isFirstThis = false;
                        if (!isset($this->map[$mapperKey])) {
                            throw new \UnexpectedValueException('Mapper for relation ' . $mapperKey . ' not exist');
                        }
                    }
                }
                foreach ($relationEntity as $e) {
                    $objectRelation = new \ReflectionObject($e);
                    $property = $objectRelation->getProperty($relation[1]);
                    $property->setAccessible(true);
                    $property->setValue($e, $entity);
                    $this->collector->add($e);
                    if (!is_null($mapperKey)) {
                        $this->many[$mapperKey][] = $isFirstThis ? array($entity, $e) : array($e, $entity);
                    }
                }
            } else {
                $objectRelation = new \ReflectionObject($relationEntity);
                $property = $objectRelation->getProperty($relation[1]);
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
            } else {
                $objectRelation = new \ReflectionObject($relationEntity);
                $property = $objectRelation->getProperty($relation[1]);
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
            $sqo = new \Scoop\Storage\SQO($this->map[$key]['table']);
            $entities = explode(':', $key);
            $idNames = array_map(array($this, 'getIdName'), $entities);
            $fields = array();
            foreach ($this->map[$key]['columns'] as $name => $column) {
                if (isset($column['foreign'])) {
                    $index = array_search($column['foreign'], $entities);
                    if ($index !== false) {
                        $fields[$index] = $name;
                    }
                }
            }
            ksort($fields);
            $create = $sqo->create($fields);
            foreach ($relation as $entities) {
                $id = array();
                foreach ($entities as $i => $entity) {
                    $object = new \ReflectionObject($entity);
                    $prop = $object->getProperty($idNames[$i]);
                    $prop->setAccessible(true);
                    $id[$i] = $prop->getValue($entity);
                }
                $create->create($id);
            }
            $create->run();
        }
    }
}
