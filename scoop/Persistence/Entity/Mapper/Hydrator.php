<?php

namespace Scoop\Persistence\Entity\Mapper;

class Hydrator
{
    private $identityMap;
    private $typeMapper;
    private $accessor;
    private $plan;

    public function __construct($identityMap, $typeMapper, $accessor, $plan)
    {
        $this->identityMap = $identityMap;
        $this->typeMapper = $typeMapper;
        $this->accessor = $accessor;
        $this->plan = $plan;
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
            $entity = $this->plan->createObject($className);
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

    private function hydrate($className, $entity, $fields, $row, $isNew)
    {
        $valueObjects = array();
        $accessor = $this->accessor->get($className);
        $snapshot = array();
        $plan = $this->plan->get($className, $fields);
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
                    $valueObjects[$propName] = $this->plan->createObject($field['type']);
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
}
