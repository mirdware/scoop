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
    private $fieldTypes = array();
    private $accessor;

    public function __construct($entityMap, $valueMap, $typeMapper, $accessor)
    {
        $this->entityMap = $entityMap;
        $this->valueMap = $valueMap;
        $this->accessor = $accessor;
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
        $key = $this->getKey($entity);
        if (isset($this->persisted[$key])) {
            $this->removed[$key] = $entity;
            $this->attach($key, $entity);
        }
    }

    public function save()
    {
        foreach ($this->entities as $entity) {
            $concreteClassName = get_class($entity);
            $className = $concreteClassName;
            $fields = array($className => $this->getFields($entity, $className, $this->entityMap[$className]));
            while ($parent = get_parent_class($className)) {
                $className = $parent;
                $fields[$className] = $this->getFields($entity, $className, $this->entityMap[$className]);
            }
            $this->execute($entity, $fields);
            $key = $this->updateKey($entity, $concreteClassName, $className);
            $this->persisted[$key] = compact('entity', 'fields');
        }
        foreach ($this->removed as $key => $entity) {
            unset($this->persisted[$key], $this->entities[$entity], $this->attached[$key]);
        }
        $this->removed = array();
    }

    public function contains($entity)
    {
        return isset($this->entities[$entity]);
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
        if (isset($this->persisted[$key])) {
            return $this->persisted[$key]['entity'];
        }
        $entity = isset($this->attached[$key]) ? $this->attached[$key] : $this->createObject($className);
        $this->setFields($className, $entity, $names, $row);
        $fields = array($className => $this->getRowFields($row, $names, $this->entityMap[$className]['properties']));
        $index = 0;
        while ($parent = get_parent_class($className)) {
            $this->setFields($parent, $entity, $names[$index], $row);
            $fields[$parent] = $this->getRowFields($row, $names[$index], $this->entityMap[$parent]['properties']);
            $className = $parent;
            $index++;
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

    private function getFieldTypes($className)
    {
        if (!isset($this->fieldTypes[$className])) {
            $types = array();
            foreach ($this->entityMap[$className]['properties'] as $propName => $propDef) {
                $fieldName = isset($propDef['column']) ? $propDef['column'] : $this->toColumn($propName);
                $type = $propDef['type'];
                if (isset($this->valueMap[$type])) {
                    $valueMap = $this->valueMap[$type];
                    if (count($valueMap) > 1) {
                        foreach ($valueMap as $voName => $voDef) {
                            $fName = $fieldName . '_' . (isset($voDef['column']) ? $voDef['column'] : $this->toColumn($voName));
                            $types[$fName] = $voDef['type'];
                        }
                    } else {
                        $voName = key($valueMap);
                        $types[$fieldName] = $valueMap[$voName]['type'];
                    }
                } else {
                    $types[$fieldName] = $type;
                }
            }
            $this->fieldTypes[$className] = $types;
        }
        return $this->fieldTypes[$className];
    }

    private function setFields($className, $entity, $fields, $row)
    {
        $valueObjects = array();
        $properties = $this->entityMap[$className]['properties'];
        $accessor = $this->accessor->get($className);
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
                $propClass = $properties[$propName]['type'];
                if (!isset($valueObjects[$propName])) {
                    $valueObjects[$propName] = $this->createObject($propClass);
                }
                $this->accessor->get($propClass)($valueObjects[$propName], $voProp, $value);
                $value = $valueObjects[$propName];
            } else {
                $value = $this->typeMapper->getEntityValue($properties[$propName]['type'], $value);
            }
            if ($accessor($entity, $propName) === null) {
                $accessor($entity, $propName, $value);
            }
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
            } else {
                $property = $this->toProperty($column);
                $column = isset($map[$property]['column']) ? $map[$property]['column'] : $column;
            }
            $fields[$column] = $row[$name];
        }
        return $fields;
    }

    private function getFields($entity, $className, $mapper)
    {
        $fields = array();
        $accessor = $this->accessor->get($className);
        foreach ($mapper['properties'] as $propName => $propDef) {
            $fieldName = isset($propDef['column']) ? $propDef['column'] : $this->toColumn($propName);
            $value = $accessor($entity, $propName);
            if (isset($mapper['relations'][$propName]) && is_object($value)) {
                $relatedClassName = get_class($value);
                while ($parent = get_parent_class($relatedClassName)) {
                    $relatedClassName = $parent;
                }
                $idName = $this->getIdName($relatedClassName);
                $value = $this->accessor->get($relatedClassName)($value, $idName);
            }
            $type = $propDef['type'];
            if (isset($this->valueMap[$type])) {
                $valueMap = $this->valueMap[$type];
                if ($value !== null && !is_a($value, $type)) {
                    throw new \UnexpectedValueException(gettype($value) . ' not is a ' . $type);
                }
                $voAccessor = $this->accessor->get($type);
                if (count($valueMap) > 1) {
                    foreach ($valueMap as $name => $prop) {
                        $fName = $fieldName . '_' . (isset($prop['column']) ? $prop['column'] : $this->toColumn($name));
                        $fields[$fName] = $voAccessor($value, $name);
                    }
                } else {
                    $fields[$fieldName] = $voAccessor($value, key($valueMap));
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
                    $updatedFields = $this->getChangedFields($className, $this->persisted[$key]['fields'][$className], $value);
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
        $className = get_class($entity);
        $rootClassName = $className;
        while ($parent = get_parent_class($rootClassName)) {
            $rootClassName = $parent;
        }
        $idName = $this->getIdName($rootClassName);
        $id = $this->accessor->get($rootClassName)($entity, $idName);
        return $className . ':' . ($id ? $id : spl_object_hash($entity));
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

    private function toColumn($property)
    {
        return strtolower(preg_replace("/([a-z])([A-Z])/", "$1_$2", $property));
    }

    private function toProperty($column)
    {
        return lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $column))));
    }

    private function getChangedFields($className, &$persistedFields, $entityFields)
    {
        $types = $this->getFieldTypes($className);
        $fields = array();
        foreach ($persistedFields as $key => $oldValue) {
            $type = isset($types[$key]) ? $types[$key] : null;
            if (!$this->typeMapper->isSame($type, $oldValue, $entityFields[$key])) {
                $fields[$key] = $entityFields[$key];
                $persistedFields[$key] = $fields[$key];
            }
        }
        return $fields;
    }

    private function updateKey($entity, $concreteClassName, $rootClassName)
    {
        $idName = $this->getIdName($rootClassName);
        $properties = $this->entityMap[$rootClassName]['properties'];
        if (
            !isset($this->persisted[$this->entities[$entity]]) &&
            isset($properties[$idName]['type']) &&
            $this->typeMapper->hasAutoIncrement($properties[$idName]['type'])
        ) {
            $accessor = $this->accessor->get($rootClassName);
            $id = $accessor($entity, $idName);
            $id = $id ? $id : $this->statements[$rootClassName]->getLastId();
            $accessor($entity, $idName, $id);
            $this->entities[$entity] = "$concreteClassName:$id";
        }
        return $this->entities[$entity];
    }
}
