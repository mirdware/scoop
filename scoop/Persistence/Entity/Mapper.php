<?php

namespace Scoop\Persistence\Entity;

class Mapper
{
    private $entities;
    private $attached;
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
        $this->attached = array();
        $this->statements = array();
    }

    public function add($entity)
    {
        $key = $this->getKey($entity);
        $this->attach($key, $entity);
        unset($this->removed[$key]);
    }

    public function remove($entity)
    {
        if ($this->isMarked($entity)) {
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
            $properties = $object->getProperties();
            $fields = array($className => $this->getFields($entity, $this->entityMap[$className], $properties));
            while ($parent = $object->getParentClass()) {
                $className = $parent->getName();
                $properties = $parent->getProperties();
                $fields[$className] = $this->getFields($entity, $this->entityMap[$className], $properties);
                $object = $parent;
            }
            $this->execute($entity, $fields);
            $key = $this->updateKey($entity, $object, $className);
            $this->persisted[$key] = compact('entity', 'fields');
        }
    }

    public function make($className, $id, $row, $names)
    {
        $key = $className . ':' . $id;
        $fields = array();
        if (isset($this->persisted[$key])) {
            return $this->persisted[$key]['entity'];
        }
        $entity = isset($this->attached[$key]) ? $this->attached[$key] : $this->createObject($className);
        $object = new \ReflectionObject($entity);
        $this->setFields($object, $entity, $names, $row);
        $fields[$className] = $this->getRowFields($row, $names, $this->entityMap[$className]);
        $index = 0;
        while ($parent = $object->getParentClass()) {
            $className = $parent->getName();
            $this->setFields($parent, $entity, $names[$index], $row);
            $fields[$className] = $this->getRowFields($row, $names, $this->entityMap[$className]);
            $index++;
            $object = $parent;
        }
        //$this->attach($key, $entity);
        $this->persisted[$key] = compact('entity', 'fields');
        return $entity;
    }

    public function isMarked($entity)
    {
        return $this->entities->contains($entity);
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
        if (isset($this->entityMap[$className]['properties'][$idName]['column'])) {
            return $this->entityMap[$className]['properties'][$idName]['column'];
        }
        return $this->toColumn($idName);
    }

    private function setFields($object, $entity, $fields, $row)
    {
        $valueObjects = array();
        foreach ($row as $name => $value) {
            if (!isset($fields[$name])) continue;
            $propName = $this->toProperty($fields[$name]);
            $vo = explode('.', $fields[$name]);
            if (isset($vo[1])) {
                if (preg_match('/([^\$]+)\$v\$(.*)/', $name, $match)) {
                    $propName = $this->toProperty($match[1]);
                    $voProp = $this->toProperty($match[2]);
                } else {
                    $propName = $this->toProperty($vo[1]);
                    $voProp = 'value';
                }
                if (!isset($valueObjects[$propName])) {
                    $voClass = $this->entityMap[$object->getName()]['properties'][$propName]['type'];
                    $instance = $this->createObject($voClass);
                    $ref = new \ReflectionObject($instance);
                    $valueObjects[$propName] = compact('instance', 'ref');
                }
                $this->getProperty($valueObjects[$propName]['ref'], $voProp)->setValue($valueObjects[$propName]['instance'], $value);
                $value = $valueObjects[$propName]['instance'];
            }
            $property = $this->getProperty($object, $propName);
            if ($property->isInitialized($entity)) continue;
            $property->setValue($entity, $value);
        }
    }

    private function getRowFields($row, $names, $mapper)
    {
        $fields = array();
        foreach ($names as $name => $prop) {
            $vo = explode('.', $prop);
            $column = isset($vo[1]) ? $vo[1] : $vo[0];
            $property = $this->toProperty($column);
            $column = isset($mapper['properties'][$property]['column']) ? $mapper['properties'][$property]['column'] : $column;
            $fields[$column] = $row[$name];
        }
        return $fields;
    }

    private function getFields($entity, $mapper, $properties)
    {
        $fields = array();
        foreach ($properties as $prop) {
            $propName = $prop->getName();
            $fieldName = isset($mapper['properties'][$propName]['column']) ?
            $mapper['properties'][$propName]['column'] :
            $this->toColumn($propName);
            if (!isset($mapper['properties'][$propName])) {
                continue;
            }
            $prop->setAccessible(true);
            $value = $prop->getValue($entity);
            if (isset($mapper['relations'][$propName]) && is_object($value)) {
                $object = new \ReflectionObject($value);
                while ($parent = $object->getParentClass()) {
                    $object = $parent;
                }
                $idName = $this->getIdName($object->getName());
                $value = $this->getProperty($object, $idName)->getValue($value);
            }
            if (isset($this->valueMap[$mapper['properties'][$propName]['type']])) {
                $valueObject = $mapper['properties'][$propName]['type'];
                $valueMap = $this->valueMap[$valueObject];
                if (!is_a($value, $valueObject)) {
                    throw new \UnexpectedValueException(gettype($value) . ' not is a ' . $valueObject);
                }
                $object = new \ReflectionObject($value);
                if (count($valueMap) > 1) {
                    foreach ($valueMap as $name => $prop) {
                        $fName = $fieldName . '_' . (isset($prop['column']) ? $prop['column'] : $this->toColumn($name));
                        $fields[$fName] = $this->getproperty($object, $name)->getValue($value);
                    }
                } else {
                    $fields[$fieldName] = $this->getproperty($object, key($valueMap))->getValue($value);
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
        if (isset($this->persisted[$key])) {
            $params = array('id' => substr($key, $index + 1));
            if (isset($this->removed[$key])) {
                foreach ($fields as $className => $value) {
                    $idName = $this->getTableId($className);
                    $this->getStatement($className)
                    ->delete()
                    ->restrict($idName . '=:id')
                    ->run($params);
                }
            } else {
                foreach ($fields as $className => $value) {
                    $updatedFields = $this->getChangedFields($value, $this->persisted[$key]['fields'][$className]);
                    if (empty($updatedFields)) {
                        continue;
                    }
                    $this->persisted[$key]['fields'][$className] = $fields;
                    $idName = $this->getTableId($className);
                    $this->getStatement($className)
                    ->update($updatedFields)
                    ->restrict($idName . '=:id')
                    ->run($params);
                }
            }
        } else {
            $fields = array_reverse($fields, true);
            $id = null;
            foreach ($fields as $className => $value) {
                $idName = $this->getTableId($className);
                $value[$idName] = isset($value[$idName]) ? $value[$idName] : $id;
                $statement = $this->getStatement($className);
                $value = $this->clearNulls($value);
                $statement->create($value)->run();
                $id = $value[$idName] ? $value[$idName] : $statement->getLastId();
            }
        }
    }

    private function getStatement($className)
    {
        if (!isset($this->entityMap[$className]['table'])) {
            throw new \RuntimeException($className . ' not mapper configured');
        }
        if (!isset($this->statements[$className])) {
            $this->statements[$className] = new \Scoop\Persistence\SQO($this->entityMap[$className]['table']);
        }
        return $this->statements[$className];
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
        if ($this->isMarked($entity)) {
            return $this->entities[$entity];
        }
        $object = new \ReflectionObject($entity);
        $className = $object->getName();
        $idName = $this->getIdName($className);
        while ($parent = $object->getParentClass()) {
            $className = $parent->getName();
            $idName = $this->getIdName($className);
            $object = $parent;
        }
        $id = $this->getProperty($object, $idName)->getValue($entity);
        return $className . ':' . ($id ? $id : uniqid());
    }

    private function attach($key, $entity)
    {
        if (isset($this->attached[$key])) {
            unset($this->entities[$entity]);
        }
        if (isset($this->persisted[$key])) {
            $this->persisted[$key]['entity'] = $entity;
        }
        if (isset($this->removed[$key])) {
            $this->removed[$key] = $entity;
        }
        $this->entities[$entity] = $key;
        $this->attached[$key] = $entity;
    }

    private function createObject($className)
    {
        $reflectionClass = new \ReflectionClass($className);
        return $reflectionClass->newInstanceWithoutConstructor();
    }

    private function getProperty($object, $name)
    {
        $prop = $object->getProperty($name);
        $prop->setAccessible(true);
        return $prop;
    }

    private function toColumn($property)
    {
        return strtolower(preg_replace("/([a-z])([A-Z])/", "$1_$2", $property));
    }

    private function toProperty($column)
    {
        return lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $column))));
    }

    private function getChangedFields($currFields, $prevFields) {
        $fields = array();
        foreach ($currFields as $key => $field) {
            if ($currFields[$key] !== $prevFields[$key]) {
                $fields[$key] = $currFields[$key];
            }
        }
        return $fields;
    }

    private function updateKey($entity, $object, $className)
    {
        $idName = $this->getIdName($className);
        if (!isset($this->persisted[$this->entities[$entity]]) && isset($this->entityMap[$className]['properties'][$idName]['type'])) {
            $type = explode(':', $this->entityMap[$className]['properties'][$idName]['type']);
            if (strtoupper(array_pop($type)) === 'SERIAL') {
                $property = $this->getProperty($object, $idName);
                $id = $property->getvalue($entity);
                $id = $id ? $id : $this->statements[$className]->getLastId();
                $property->setValue($entity, $id);
                $this->entities[$entity] = "$className:$id";
            }
        }
        return $this->entities[$entity];
    }
}
