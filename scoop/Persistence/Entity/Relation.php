<?php

namespace Scoop\Persistence\Entity;

class Relation
{
    const ONE_TO_ONE = 1;
    const ONE_TO_MANY = 2;
    const MANY_TO_ONE = 3;
    const MANY_TO_MANY = 4;
    private $many;
    private $touched;
    private $loaded;
    private $mapper;
    private $relationMap;
    private $manager;
    private $accessor;

    public function __construct($map, $mapper, $manager, $accessor)
    {
        $this->relationMap = $map;
        $this->mapper = $mapper;
        $this->manager = $manager;
        $this->accessor = $accessor;
        $this->many = array();
        $this->touched = array();
        $this->loaded = array();
    }

    public function track($name, $ownerId, $relatedEntities)
    {
        $name = explode(':', $name)[1];
        $this->loaded[$name][$ownerId] = $relatedEntities;
    }

    public function add($entity, $relations)
    {
        $entityName = get_class($entity);
        foreach ($relations as $name => $relation) {
            $declaringClass = $this->accessor->getDeclaringClass($entityName, $name);
            if (!$declaringClass) continue;
            $accessor = $this->accessor->get($declaringClass);
            $relationEntity = $accessor($entity, $name);
            if ($relationEntity === null) continue;
            list($relationName, $mapperKey) = $this->getPropertyRelation($relation);
            if (is_array($relationEntity)) {
                if ($mapperKey !== null) {
                    if (!isset($this->many[$mapperKey])) {
                        $this->many[$mapperKey] = array();
                    }
                    $this->touched[$mapperKey][] = $entity;
                    $this->detachRemoved($entityName, $entity, $mapperKey, $relationName, $relation[0], $relationEntity);
                }
                foreach ($relationEntity as $e) {
                    if (!$this->mapper->contains($e)) {
                        $this->manager->save($e);
                    }
                    if ($mapperKey !== null) {
                        $this->many[$mapperKey][] = array($entity, $e);
                    }
                    $classRelated = $this->accessor->getDeclaringClass($relation[0], $relationName);
                    if (!$classRelated) continue;
                    $relatedAccessor = $this->accessor->get($classRelated);
                    $value = $entity;
                    if ($mapperKey !== null) {
                        $value = $relatedAccessor($e, $relationName);
                        if (!$value) {
                            $value = array($entity);
                        } elseif (!in_array($entity, $value)) {
                            array_push($value, $entity);
                        }
                    }
                    $relatedAccessor($e, $relationName, $value);
                }
            } elseif (is_object($relationEntity)) {
                if (!$this->mapper->contains($relationEntity)) {
                    $this->manager->save($relationEntity);
                }
                $classRelated = $this->accessor->getDeclaringClass($relation[0], $relationName);
                if (!$classRelated) continue;
                $relatedAccessor = $this->accessor->get($classRelated);
                $value = $relatedAccessor($relationEntity, $relationName);
                if (!is_array($value)) {
                    $value = $entity;
                } elseif (!in_array($entity, $value)) {
                    array_push($value, $entity);
                }
                $relatedAccessor($relationEntity, $relationName, $value);
            }
        }
    }

    public function remove($entity, $relations)
    {
        $entityName = get_class($entity);
        foreach ($relations as $name => $relation) {
            $declaringClass = $this->accessor->getDeclaringClass($entityName, $name);
            if (!$declaringClass) continue;
            $accessor = $this->accessor->get($declaringClass);
            $relationEntity = $accessor($entity, $name);
            if (!$relationEntity) {
                continue;
            }
            if (is_array($relationEntity)) {
                foreach ($relationEntity as $e) {
                    $this->manager->remove($e);
                }
            } elseif (is_object($relationEntity)) {
                $relationName = $this->getPropertyRelation($relation)[0];
                $classRelated = $this->accessor->getDeclaringClass(get_class($relationEntity), $relationName);
                if (!$classRelated) continue;
                $relatedAccessor = $this->accessor->get($classRelated);
                $value = $relatedAccessor($relationEntity, $relationName);
                if (is_array($value)) {
                    $index = array_search($entity, $value);
                    if ($index !== false) {
                        array_splice($value, $index, 1);
                    }
                } else {
                    $this->manager->remove($relationEntity);
                    $value = null;
                }
                $relatedAccessor($relationEntity, $relationName, $value);
            }
        }
    }

    public function save()
    {
        foreach ($this->many as $key => $relation) {
            $sqo = new \Scoop\Persistence\SQO($this->relationMap[$key]['table']);
            $fields = array();
            $idNames = array();
            foreach ($this->relationMap[$key]['entities'] as $name => $definition) {
                if (isset($definition['column'])) {
                    $fields[$name] = $definition['column'];
                    $idNames[$name] = $this->mapper->getIdName($name);
                }
            }
            $create = $sqo->create(array_values($fields));
            $owners = new \SplObjectStorage();
            $seen = array();
            $touched = isset($this->touched[$key]) ? $this->touched[$key] : array();
            foreach ($touched as $ownerEntity) {
                if (isset($owners[$ownerEntity])) continue;
                list($name, $value) = $this->getRelationValue($ownerEntity, $key, $idNames);
                $sqo->delete()
                ->restrict($fields[$name] . '=:ownerId')
                ->run(array('ownerId' => $value));
                $owners[$ownerEntity] = true;
            }
            foreach ($relation as $entities) {
                $relationIds = array();
                list($name, $value) = $this->getRelationValue($entities[0], $key, $idNames);
                $fieldName = $fields[$name];
                $relationIds[$fieldName] = $value;
                if (!isset($owners[$entities[0]])) {
                    $sqo->delete()
                    ->restrict($fieldName . '=:ownerId')
                    ->run(array('ownerId' => $relationIds[$fieldName]));
                    $owners[$entities[0]] = true;
                }
                list($name, $value) = $this->getRelationValue($entities[1], $key, $idNames);
                $relationIds[$fields[$name]] = $value;
                ksort($relationIds);
                $dedupeKey = implode(':', $relationIds);
                if (isset($seen[$dedupeKey])) continue;
                $seen[$dedupeKey] = true;
                $create->create($relationIds);
            }
            if ($create->hasData()) {
                $create->run();
            }
        }
        $this->many = array();
        $this->touched = array();
    }

    private function detachRemoved($entityClass, $entity, $mapperKey, $relationName, $removeClass, $currentEntities)
    {
        $idName = $this->mapper->getIdName($entityClass);
        $accessor = $this->accessor->get(
            $this->accessor->getDeclaringClass($entityClass, $idName)
        );
        $ownerId = $accessor($entity, $idName);
        if (!isset($this->loaded[$mapperKey]) ||!isset($this->loaded[$mapperKey][$ownerId])) {
            return;
        }
        $currentIndexed = array();
        $idName = $this->mapper->getIdName($removeClass);
        $classRelated = $this->accessor->getDeclaringClass($removeClass, $idName);
        if (!$classRelated) return;
        $accessor = $this->accessor->get($classRelated);
        foreach ($currentEntities as $e) {
            $currentIndexed[$accessor($e, $idName)] = $e;
        }
        $removed = array_diff_key($this->loaded[$mapperKey][$ownerId], $currentIndexed);
        foreach ($removed as $removedEntity) {
            $value = $accessor($removedEntity, $relationName);
            $index = array_search($entity, $value, true);
            if ($index !== false) {
                array_splice($value, $index, 1);
                $accessor($removedEntity, $relationName, $value);
            }
        }
        $this->loaded[$mapperKey][$ownerId] = $currentIndexed;
    }

    private function getRelationValue($entity, $key, $idNames)
    {
        $name = get_class($entity);
        if (!isset($idNames[$name])) {
            throw new \UnexpectedValueException("$name not is present on $key relation");
        }
        $idName = $idNames[$name];
        $accessor = $this->accessor->get(
            $this->accessor->getDeclaringClass($name, $idName)
        );
        return array($name, $accessor($entity, $idName));
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
