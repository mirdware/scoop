<?php

namespace Scoop\Persistence\Entity\Mapper;

class Hydrator
{
    private $identityMap;
    private $entityMap;
    private $valueMap;
    private $typeMapper;
    private $accessor;
    private $reflectionClasses = array();
    private $plans = array();

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
        $isNew = $entity === null;
        if ($isNew) {
            $entity = $this->createObject($className);
        }
        $fields = array($className => $this->hydrate($className, $entity, $names, $row, $isNew));
        $index = 0;
        while ($parent = get_parent_class($className)) {
            $fields[$parent] = $this->hydrate($parent, $entity, $names[$index], $row, $isNew);
            $className = $parent;
            $index++;
        }
        $this->identityMap->setPersisted($key, $entity, $fields);
        return $entity;
    }

    public function createObject($className)
    {
        if (!isset($this->reflectionClasses[$className])) {
            $this->reflectionClasses[$className] = new \ReflectionClass($className);
        }
        return $this->reflectionClasses[$className]->newInstanceWithoutConstructor();
    }

    private function hydrate($className, $entity, $fields, $row, $isNew)
    {
        $valueObjects = array();
        $accessor = $this->accessor->get($className);
        $snapshot = array();
        $plan = $this->getPlan($className, $fields);
        foreach ($plan as $field) {
            $name = $field['name'];
            $hasValue = array_key_exists($name, $row);
            $value = $hasValue ? $row[$name] : null;
            $snapshot[$field['column']] = $value;
            if (!$hasValue) continue;
            $propName = $field['property'];
            if (isset($field['valueProperty'])) {
                if (!array_key_exists($propName, $valueObjects)) {
                    if (!$isNew && $accessor($entity, $propName) !== null) {
                        $valueObjects[$propName] = null;
                        continue;
                    }
                    $valueObjects[$propName] = $this->createObject($field['type']);
                }
                if ($valueObjects[$propName] === null) continue;
                $valueAccessor = $this->accessor->get($field['type']);
                $valueAccessor($valueObjects[$propName], $field['valueProperty'], $value);
                $value = $valueObjects[$propName];
            } else {
                $value = $this->typeMapper->getEntityValue($field['type'], $value);
            }
            if ($isNew || $accessor($entity, $propName) === null) {
                $accessor($entity, $propName, $value);
            }
        }
        return $snapshot;
    }

    private function getPlan($className, $fields)
    {
        if (isset($this->plans[$className])) {
            foreach ($this->plans[$className] as $cached) {
                if ($cached['fields'] === $fields) {
                    return $cached['plan'];
                }
            }
        }
        $plan = array();
        $properties = $this->entityMap[$className]['properties'];
        foreach ($fields as $name => $column) {
            if (!is_string($column)) continue;
            $vo = explode('.', $column);
            if (isset($vo[1])) {
                $snapshotColumn = $vo[1];
                if (preg_match('/([^\$]+)\$v\$(.*)/', $name, $match)) {
                    $property = \Scoop\Persistence\Entity\Mapper::toProperty($match[1]);
                    $valueProperty = \Scoop\Persistence\Entity\Mapper::toProperty($match[2]);
                } else {
                    $property = \Scoop\Persistence\Entity\Mapper::toProperty($vo[1]);
                    $valueProperty = 'value';
                }
            } else {
                $property = \Scoop\Persistence\Entity\Mapper::toProperty($column);
                $snapshotColumn = isset($properties[$property]['column']) ?
                    $properties[$property]['column'] : $column;
                $valueProperty = null;
            }
            $plan[] = array(
                'name' => $name,
                'property' => $property,
                'column' => $snapshotColumn,
                'type' => $properties[$property]['type'],
                'valueProperty' => $valueProperty
            );
        }
        $this->plans[$className][] = array('fields' => $fields, 'plan' => $plan);
        return $plan;
    }
}
