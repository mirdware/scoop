<?php

namespace Scoop\Persistence\Entity;

class Mapper
{
    private $entities;
    private $attached;
    private $persisted;
    private $removed;
    private $statements;
    private $typeMapper;
    private $entityMap;
    private $valueMap;

    public function __construct($entityMap, $valueMap, $typeMapper)
    {
        $this->entityMap = $entityMap;
        $this->valueMap = $valueMap;
        $this->entities = new \SplObjectStorage();
        $this->persisted = array();
        $this->removed = array();
        $this->attached = array();
        $this->statements = array();
        $this->typeMapper = $typeMapper;
    }

    public function add($entity)
    {
        $key = $this->getKey($entity);
        $this->attach($key, $entity);
        unset($this->removed[$key]);
    }

    public function remove($entity)
    {
        if ($this->contains($entity)) {
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

    public function contains($entity)
    {
        return $this->entities->contains($entity);
    }

    public function detach($entity)
    {
        if ($this->contains($entity)) {
            $key = $this->entities[$entity];
            unset(
                $this->entities[$entity],
                $this->attached[$key],
                $this->removed[$key]
            );
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
        $fields[$className] = $this->getRowFields($row, $names, $this->entityMap[$className]['properties']);
        $index = 0;
        while ($parent = $object->getParentClass()) {
            $className = $parent->getName();
            $this->setFields($parent, $entity, $names[$index], $row);
            $fields[$className] = $this->getRowFields($row, $names[$index], $this->entityMap[$className]['properties']);
            $index++;
            $object = $parent;
        }
        $this->persisted[$key] = compact('entity', 'fields');
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
        if (isset($this->entityMap[$className]['properties'][$idName]['column'])) {
            return $this->entityMap[$className]['properties'][$idName]['column'];
        }
        return $this->toColumn($idName);
    }

    private function setFields($object, $entity, $fields, $row)
    {
        $valueObjects = array();
        $properties = $this->entityMap[$object->getName()]['properties'];
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
                    $instance = $this->createObject($properties[$propName]['type']);
                    $ref = new \ReflectionObject($instance);
                    $valueObjects[$propName] = compact('instance', 'ref');
                }
                $this->getProperty($valueObjects[$propName]['ref'], $voProp)->setValue($valueObjects[$propName]['instance'], $value);
                $value = $valueObjects[$propName]['instance'];
            } else {
                $value = $this->typeMapper->getEntityValue($properties[$propName]['type'], $value);
            }
            if (!$object->hasProperty($propName)) continue;
            $property = $this->getProperty($object, $propName);
            if ($property->isInitialized($entity) && $property->getValue($entity) !== null) continue;
            $property->setValue($entity, $value);
        }
    }

    private function getRowFields($row, $names, $map)
    {
        $fields = array();
        foreach ($names as $name => $column) {
            if (!is_string($column)) continue;
            $vo = explode('.', $column);
            if (isset($vo[1])) {
                $column = $vo[1];
                $vo = explode('$a$', $name);
                $vo = $vo[count($vo) - 1];
                $vo = explode('$v$', $vo);
                $property = $this->toProperty($vo[0]);
                $vo = isset($vo[1]) ? $vo[1] : 'value';
                $type = $this->valueMap[$map[$property]['type']][$vo]['type'];
            } else {
                $property = $this->toProperty($column);
                $column = isset($map[$property]['column']) ? $map[$property]['column'] : $column;
                $type = $map[$property]['type'];
            }
            $fields[$column] = array('type' => $type, 'value' => $row[$name]);
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
            $type = $mapper['properties'][$propName]['type'];
            if (isset($this->valueMap[$type])) {
                $valueMap = $this->valueMap[$type];
                if (!is_a($value, $type)) {
                    throw new \UnexpectedValueException(gettype($value) . ' not is a ' . $type);
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
                $fields[$fieldName] = $this->typeMapper->getRowValue($type, $value);
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
                    $updatedFields = $this->getChangedFields($this->persisted[$key]['fields'][$className], $value);
                    if (empty($updatedFields)) {
                        continue;
                    }
                    $idName = $this->getTableId($className);
                    $this->getStatement($className)
                    ->update($updatedFields)
                    ->restrict($idName . '=:id')
                    ->run($params);
                }
            }
        } else {
            $baseClassName = array_keys($fields)[0];
            $fields = array_reverse($fields, true);
            $id = null;
            foreach ($fields as $className => $value) {
                if (isset($this->entityMap[$className]['discriminator'])) {
                    extract($this->entityMap[$className]['discriminator']);
                    if (isset($column, $map[$baseClassName])) {
                        $value[$column] = $map[$baseClassName];
                    }
                }
                $idName = $this->getTableId($className);
                $value[$idName] = isset($value[$idName]) ? $value[$idName] : $id;
                $statement = $this->getStatement($className);
                $value = $this->clearNulls($value);
                $statement->create($value)->run();
                $id = isset($value[$idName]) ? $value[$idName] : $statement->getLastId();
                $baseClassName = $className;
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
        if ($this->contains($entity)) {
            return $this->entities[$entity];
        }
        $object = new \ReflectionObject($entity);
        $className = $object->getName();
        $idName = $this->getIdName($className);
        while ($parent = $object->getParentClass()) {
            $classParentName = $parent->getName();
            $idName = $this->getIdName($classParentName);
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

    private function getChangedFields(&$persistedFields, $entityFields) {
        $fields = array();
        foreach ($persistedFields as $key => $field) {
            if (!$this->typeMapper->isSame($field['type'], $field['value'], $entityFields[$key])) {
                $fields[$key] = $entityFields[$key];
                $persistedFields[$key]['value'] = $fields[$key];
            }
        }
        return $fields;
    }

    private function updateKey($entity, $object, $className)
    {
        $idName = $this->getIdName($className);
        $properties = $this->entityMap[$className]['properties'];
        if (!isset($this->persisted[$this->entities[$entity]]) && isset($properties[$idName]['type'])) {
            if ($this->typeMapper->hasAutoIncrement($properties[$idName]['type'])) {
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
