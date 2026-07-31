<?php

namespace Scoop\Persistence\Entity\Mapper;

class Hydrator
{
    private $identityMap;
    private $entityMap;
    private $valueMap;
    private $typeMapper;
    private $accessor;

    public function __construct($identityMap, $entityMap, $valueMap, $typeMapper, $accessor)
    {
        $this->identityMap = $identityMap;
        $this->entityMap = $entityMap;
        $this->valueMap = $valueMap;
        $this->typeMapper = $typeMapper;
        $this->accessor = $accessor;
    }

    public function make($className, $id, $row, $names)
    {
        $key = $className . ':' . $id;
        if ($this->identityMap->isPersisted($key)) {
            return $this->identityMap->getPersistedEntity($key);
        }
        $entity = $this->identityMap->getAttachedEntity($key);
        if ($entity === null) {
            $entity = $this->createObject($className);
        }
        $this->setFields($className, $entity, $names, $row);
        $fields = array($className => $this->getRowFields($row, $names, $this->entityMap[$className]['properties']));
        $index = 0;
        while ($parent = get_parent_class($className)) {
            $this->setFields($parent, $entity, $names[$index], $row);
            $fields[$parent] = $this->getRowFields($row, $names[$index], $this->entityMap[$parent]['properties']);
            $className = $parent;
            $index++;
        }
        $this->identityMap->setPersisted($key, $entity, $fields);
        return $entity;
    }

    public function createObject($className)
    {
        $reflectionClass = new \ReflectionClass($className);
        return $reflectionClass->newInstanceWithoutConstructor();
    }

    private function setFields($className, $entity, $fields, $row)
    {
        $valueObjects = array();
        $properties = $this->entityMap[$className]['properties'];
        $accessor = $this->accessor->get($className);
        foreach ($row as $name => $value) {
            if (!isset($fields[$name])) continue;
            $propName = \Scoop\Persistence\Entity\Mapper::toProperty($fields[$name]);
            $vo = explode('.', $fields[$name]);
            if (isset($vo[1])) {
                if (preg_match('/([^\$]+)\$v\$(.*)/', $name, $match)) {
                    $propName = \Scoop\Persistence\Entity\Mapper::toProperty($match[1]);
                    $voProp = \Scoop\Persistence\Entity\Mapper::toProperty($match[2]);
                } else {
                    $propName = \Scoop\Persistence\Entity\Mapper::toProperty($vo[1]);
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
                $property = \Scoop\Persistence\Entity\Mapper::toProperty($column);
                $column = isset($map[$property]['column']) ? $map[$property]['column'] : $column;
            }
            $fields[$column] = $row[$name];
        }
        return $fields;
    }
}
