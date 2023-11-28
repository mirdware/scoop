<?php

namespace Scoop\Persistence\Entity;

class Mapper
{
    private $entities;
    private $persisted;
    private $removed;
    private $statements;
    private $entityMap;
    private $valueMap;

    public function __construct($entityMap, $valueMap)
    {
        $this->entityMap = $entityMap;
        $this->valueMap = $valueMap;
        $this->entities = new \SplObjectStorage();
        $this->persisted = array();
        $this->removed = array();
        $this->statements = array();
    }

    public function add($entity)
    {
        $key = $this->getKey($entity);
        unset($this->removed[$key]);
    }

    public function remove($entity)
    {
        if (isset($this->entities[$entity])) {
            $key = $this->entities[$entity];
            if (isset($this->persisted[$key])) {
                $this->removed[$key] = $entity;
            }
        }
    }

    public function save()
    {
        foreach ($this->entities as $entity) {
            $object = new \ReflectionObject($entity);
            $className = $object->getName();
            $fields = $this->getFields($entity, $this->entityMap[$className], $object->getProperties());
            $this->execute($entity, $fields);
            $key = $this->updateKey($entity, $className);
            $this->persisted[$key] = $entity;
        }
    }

    public function make($className, $id, $row, $fields, $sqo)
    {
        $key = $className . ':' . $id;
        if (isset($this->persisted[$key])) {
            return $this->persisted[$key];
        }
        if (!isset($this->statements[$className])) {
            $this->statements[$className] = $sqo;
        }
        $reflectionClass = new \ReflectionClass($className);
        $constructor = $reflectionClass->getConstructor();
        $args = array_fill(0, $constructor->getNumberOfRequiredParameters(), null);
        $entity = $constructor ?
        $reflectionClass->newInstanceArgs($args) :
        $reflectionClass->newInstanceWithoutConstructor();
        $object = new \ReflectionObject($entity);
        foreach ($row as $name => $value) {
            $prop = $object->getProperty($fields[$name]);
            $prop->setAccessible(true);
            $prop->setValue($entity, $value);
        }
        $this->entities[$entity] = $key;
        $this->persisted[$key] = $entity;
        return $entity;
    }

    public function getIdName($className)
    {
        return isset($this->entityMap[$className]['id']) ? $this->entityMap[$className]['id'] : 'id';
    }

    public function getTableId($className)
    {
        $idName = 'id';
        if (isset($this->entityMap[$className]['id'])) {
            $idName = $this->entityMap[$className]['id'];
        }
        if (isset($this->entityMap[$className]['properties'][$idName]['name'])) {
            return $this->entityMap[$className]['properties'][$idName]['name'];
        }
        return strtolower(preg_replace("/([a-z])([A-Z])/", "$1_$2", $idName));
    }

    private function getFields($entity, $mapper, $properties)
    {
        $fields = array();
        foreach ($properties as $prop) {
            $propName = $prop->getName();
            $fieldName = isset($mapper['properties'][$propName]['name']) ?
            $mapper['properties'][$propName]['name'] :
            strtolower(preg_replace("/([a-z])([A-Z])/", "$1_$2", $propName));
            if (!isset($mapper['properties'][$propName])) {
                continue;
            }
            $prop->setAccessible(true);
            $value = $prop->getValue($entity);
            if (isset($mapper['relations'][$propName]) && is_object($value)) {
                $relation = $mapper['relations'][$propName];
                $idName = $this->getIdName($relation[0]);
                $object = new \ReflectionObject($value);
                $relationProp = $object->getProperty($idName);
                $relationProp->setAccessible(true);
                $value = $relationProp->getValue($value);
            }
            if (isset($this->valueMap[$mapper['properties'][$propName]['type']])) {
                $valueObject = $mapper['properties'][$propName]['type'];
                $valueMap = $this->valueMap[$valueObject];
                if (!is_a($value, $valueObject)) {
                    throw new \UnexpectedValueException(gettype($value) . ' not is a ' . $valueObject);
                }
                $object = new \ReflectionObject($value);
                $size = count($valueMap);
                foreach ($valueMap as $name => $prop) {
                    $fName = isset($prop['name']) ?
                    $prop['name'] : (
                        $size > 1 ?
                        $fieldName . '_' . strtolower(preg_replace("/([a-z])([A-Z])/", "$1_$2", $name)) :
                        $fieldName
                    );
                    $relationProp = $object->getProperty($name);
                    $relationProp->setAccessible(true);
                    $fields[$fName] = $relationProp->getValue($value);
                }
            } else {
                $fields[$fieldName] = $value;
            }
        }
        return $fields;
    }

    private function execute($entity, $fields)
    {
        $key = strval($this->entities[$entity]);
        $index = strpos($key, ':');
        $className = substr($key, 0, $index);
        if (!isset($this->entityMap[$className]['table'])) {
            throw new \RuntimeException($className . ' not mapper configured');
        }
        if (!isset($this->statements[$className])) {
            $this->statements[$className] = new \Scoop\Persistence\SQO($this->entityMap[$className]['table']);
        }
        if (isset($this->persisted[$key])) {
            $statement = $this->statements[$className];
            $params = array('id' => substr($key, $index + 1));
            $idName = $this->getTableId($className);
            if (isset($this->removed[$key])) {
                return $statement->delete()->restrict($idName . ' = :id')->run($params);
            }
            return $statement->update($fields)->restrict($idName . ' = :id')->run($params);
        }
        $fields = $this->clearNulls($fields);
        return $this->statements[$className]->create($fields)->run();
    }

    private function clearNulls($value, $only = null)
    {
        $filtered = array();
        foreach ($value as $key => $element) {
            if (($only !== null && !isset($only[$key])) || $element !== null) {
                $filtered[$key] = $element;
            }
        }
        return $filtered;
    }

    private function getKey($entity)
    {
        if (isset($this->entities[$entity])) {
            return $this->entities[$entity];
        }
        $object = new \ReflectionObject($entity);
        $className = $object->getName();
        $id = $this->getIdName($className);
        $property = $object->getProperty($id);
        $property->setAccessible(true);
        $id = $property->getValue($entity);
        $key = $id ? $className . ':' . $id : $className . ':' . uniqid();
        $this->entities[$entity] = $key;
        return $this->entities[$entity];
    }

    private function updateKey($entity, $className)
    {
        $idName = $this->getIdName($className);
        if (isset($this->entityMap[$className]['properties'][$idName]['type'])) {
            $type = explode(':', $this->entityMap[$className]['properties'][$idName]['type']);
            $isAuto = array_pop($type) === 'SERIAL';
            if ($isAuto) {
                $id = $this->statements[$className]->getLastId();
                $object = new \ReflectionObject($entity);
                $prop = $object->getProperty($idName);
                $prop->setAccessible(true);
                $prop->setValue($entity, $id);
                $this->entities[$entity] = $className . ':' . $id;
            }
        }
        return $this->entities[$entity];
    }
}
