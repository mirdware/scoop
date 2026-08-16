<?php

namespace Scoop\Persistence\Entity;

class Mapper
{
    private $entityMap;
    private $typeMapper;
    private $accessor;
    private $identityMap;
    private $hydrator;
    private $extractor;
    private $builder;

    public function __construct($entityMap, $valueMap, $typeMapper, $accessor, $builder)
    {
        $this->entityMap = $entityMap;
        $this->typeMapper = $typeMapper;
        $this->accessor = $accessor;
        $this->builder = $builder;
        $this->identityMap = new Mapper\Identity();
        $this->hydrator = new Mapper\Hydrator($this->identityMap, $entityMap, $valueMap, $typeMapper, $accessor);
        $this->extractor = new Mapper\Extractor($entityMap, $valueMap, $typeMapper, $accessor);
    }

    public function add($entity)
    {
        $key = $this->getKey($entity);
        $this->identityMap->attach($key, $entity);
        $this->identityMap->unmarkRemoved($key);
    }

    public function remove($entity)
    {
        $key = $this->getKey($entity);
        if ($this->identityMap->isPersisted($key)) {
            $this->identityMap->markRemoved($key, $entity);
            $this->identityMap->attach($key, $entity);
        }
    }

    public function save()
    {
        foreach ($this->identityMap as $entity) {
            $concreteClassName = get_class($entity);
            $className = $concreteClassName;
            $fields = array($className => $this->extractor->getFields($entity, $className, $this->entityMap[$className]));
            while ($parent = get_parent_class($className)) {
                $className = $parent;
                $fields[$className] = $this->extractor->getFields($entity, $className, $this->entityMap[$className]);
            }
            $this->execute($entity, $fields);
            $key = $this->updateKey($entity, $concreteClassName, $className);
            $this->identityMap->setPersisted($key, $entity, $fields);
        }
        $this->identityMap->purgeRemoved();
    }

    public function contains($entity)
    {
        return $this->identityMap->contains($entity);
    }

    public function detach($entity)
    {
        $this->identityMap->detach($entity);
    }

    public function make($className, $id, $row, $names)
    {
        return $this->hydrator->make($className, $id, $row, $names);
    }

    public function getIdName($className)
    {
        return self::resolveIdName($this->entityMap, $className);
    }

    public function getTableId($className)
    {
        $idName = $this->getIdName($className);
        if (isset($this->entityMap[$className]['properties'][$idName]['column'])) {
            return $this->entityMap[$className]['properties'][$idName]['column'];
        }
        return self::toColumn($idName);
    }

    public static function resolveIdName($entityMap, $className)
    {
        return isset($entityMap[$className]['id']) ? $entityMap[$className]['id'] : 'id';
    }

    public static function toColumn($property)
    {
        return strtolower(preg_replace("/([a-z])([A-Z])/", "$1_$2", $property));
    }

    public static function toProperty($column)
    {
        return lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $column))));
    }

    private function execute($entity, $fields)
    {
        $key = strval($this->identityMap->getKey($entity));
        $index = strpos($key, ':');
        if ($this->identityMap->isPersisted($key)) {
            $params = array('id' => substr($key, $index + 1));
            if ($this->identityMap->isRemoved($key)) {
                foreach ($fields as $className => $value) {
                    $idName = $this->getTableId($className);
                    $this->getStatement($className)
                    ->delete()
                    ->restrict($idName . '=:id')
                    ->run($params);
                }
            } else {
                foreach ($fields as $className => $value) {
                    $persistedFields = &$this->identityMap->getPersistedFields($key, $className);
                    $updatedFields = $this->extractor->getChangedFields($className, $persistedFields, $value);
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
                $value = $this->extractor->clearNulls($value);
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
        return $this->builder->build($this->entityMap[$className]['table']);
    }

    private function getKey($entity)
    {
        if ($this->identityMap->contains($entity)) {
            return $this->identityMap->getKey($entity);
        }
        $className = get_class($entity);
        $rootClassName = $className;
        while ($parent = get_parent_class($rootClassName)) {
            $rootClassName = $parent;
        }
        $idName = $this->getIdName($rootClassName);
        $accessor = $this->accessor->get($rootClassName);
        $id = $accessor($entity, $idName);
        return $className . ':' . ($id ? $id : spl_object_hash($entity));
    }

    private function updateKey($entity, $concreteClassName, $rootClassName)
    {
        $idName = $this->getIdName($rootClassName);
        $properties = $this->entityMap[$rootClassName]['properties'];
        $key = $this->identityMap->getKey($entity);
        if (
            !$this->identityMap->isPersisted($key) &&
            isset($properties[$idName]['type']) &&
            $this->typeMapper->hasAutoIncrement($properties[$idName]['type'])
        ) {
            $accessor = $this->accessor->get($rootClassName);
            $id = $accessor($entity, $idName);
            $id = $id ? $id : $this->getStatement($rootClassName)->getLastId();
            $accessor($entity, $idName, $id);
            $newKey = "$concreteClassName:$id";
            $this->identityMap->reassignKey($entity, $newKey);
            return $newKey;
        }
        return $key;
    }
}
