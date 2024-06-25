<?php

namespace Scoop\Persistence\Entity;

class Relation
{
    const ONE_TO_ONE = 1;
    const ONE_TO_MANY = 2;
    const MANY_TO_ONE = 3;
    const MANY_TO_MANY = 4;
    private $many;
    private $mapper;
    private $relationMap;
    private $manager;

    public function __construct($map, $mapper, $manager)
    {
        $this->relationMap = $map;
        $this->mapper = $mapper;
        $this->manager = $manager;
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
                    if ($this->mapper->isMarked($e)) continue;
                    $objectRelation = new \ReflectionObject($e);
                    $property = $objectRelation->getProperty($relationName);
                    $property->setAccessible(true);
                    $value = $entity;
                    $this->manager->save($e);
                    if (!is_null($mapperKey)) {
                        $this->many[$mapperKey][] = array($entity, $e);
                        $value = $property->getValue($e);
                        if (!$value) {
                            $value = array($entity);
                        } elseif (!in_array($entity, $value)) {
                            array_push($value, $entity);
                        }
                    }
                    $property->setValue($e, $value);
                }
            } elseif (is_object($relationEntity)) {
                if ($this->mapper->isMarked($relationEntity)) continue;
                $objectRelation = new \ReflectionObject($relationEntity);
                $property = $objectRelation->getProperty($relationName);
                $property->setAccessible(true);
                $value = $property->getValue($relationEntity);
                if (!is_array($value)) {
                    $value = $entity;
                } elseif (!in_array($entity, $value)) {
                    array_push($value, $entity);
                }
                $property->setValue($relationEntity, $value);
                $this->manager->save($relationEntity);
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
                    $this->manager->remove($e);
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
                    $this->manager->remove($relationEntity);
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
            foreach ($this->relationMap[$key]['entities'] as $name => $definition) {
                if (isset($definition['column'])) {
                    $fields[] = $definition['column'];
                    $properties[] = $name;
                }
            }
            $idNames = array_map(array($this->mapper, 'getIdName'), $properties);
            $create = $sqo->create($fields);
            foreach ($relation as $entities) {
                $id = array();
                foreach ($entities as $entity) {
                    $object = new \ReflectionObject($entity);
                    $name = $object->getName();
                    $i = array_search($name, $properties);
                    if ($i === false) {
                        throw new \UnexpectedValueException($name . ' not is present on ' . $key . ' relation');
                    }
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
