<?php

namespace Scoop\Persistence\Entity;

class Relation
{
    const ONE_TO_ONE = 1;
    const ONE_TO_MANY = 2;
    const MANY_TO_ONE = 3;
    const MANY_TO_MANY = 4;
    private $mapper;
    private $relationMap;
    private $manager;
    private $accessor;
    private $builder;
    private $many = array();
    private $loaded = array();
    private $previous = array();

    public function __construct($map, $mapper, $manager, $accessor, $builder)
    {
        $this->relationMap = $map;
        $this->mapper = $mapper;
        $this->manager = $manager;
        $this->accessor = $accessor;
        $this->builder = $builder;
    }

    public function track($name, $ownerId, $relatedEntities)
    {
        $name = explode(':', $name)[1];
        $this->loaded[$name][$ownerId] = $relatedEntities;
        $this->previous[$name][$ownerId] = $relatedEntities;
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
            $currentEntities = is_array($relationEntity) ? $relationEntity : array($relationEntity);
            if ($mapperKey !== null) {
                if (!isset($this->many[$mapperKey])) {
                    $this->many[$mapperKey] = new \SplObjectStorage();
                }
                $this->many[$mapperKey][$entity] = $relationEntity;
            } else {
                $mapperKey = $entityName . ':' . $name;
            }
            $this->symmetrize($entityName, $entity, $mapperKey, $relationName, $relation[0], $currentEntities);
            foreach ($currentEntities as $currentEntity) {
                if (is_object($currentEntity) && !$this->mapper->contains($currentEntity)) {
                    $this->manager->save($currentEntity);
                }
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
                $relationName = $this->getPropertyRelation($relation)[0];
                foreach ($relationEntity as $relatedEntity) {
                    $this->unlinkInverse($relatedEntity, $relationName, $entity);
                }
                $accessor($entity, $name, array());
            } elseif (is_object($relationEntity)) {
                $relationName = $this->getPropertyRelation($relation)[0];
                $this->unlinkInverse($relationEntity, $relationName, $entity);
                $accessor($entity, $name, null);
            }
        }
    }

    public function save()
    {
        foreach ($this->many as $key => $relation) {
            $sqo = $this->builder->build($this->relationMap[$key]['table']);
            $fields = array();
            $idNames = array();
            foreach ($this->relationMap[$key]['entities'] as $name => $definition) {
                if (isset($definition['column'])) {
                    $fields[$name] = $definition['column'];
                    $idNames[$name] = $this->mapper->getIdName($name);
                }
            }
            $create = $sqo->create(array_values($fields));
            foreach ($relation as $ownerEntity) {
                list($ownerClass, $ownerId) = $this->getRelationValue($ownerEntity, $key, $idNames);
                if (!isset($this->loaded[$key][$ownerId])) {
                    $this->loaded[$key][$ownerId] = array();
                }
                $classNames = array_keys($this->relationMap[$key]['entities']);
                $relationClass = $classNames[0] === $ownerClass ? $classNames[1] : $classNames[0];
                $relatedEntities = $this->indexEntities($relationClass, $this->many[$key][$ownerEntity]);
                $removed = array_diff_key($this->loaded[$key][$ownerId], $relatedEntities);
                $added = array_diff_key($relatedEntities, $this->loaded[$key][$ownerId]);
                $this->loaded[$key][$ownerId] = array_diff_key($this->loaded[$key][$ownerId], $removed);
                if (!empty($removed)) {
                    $relatedField = $fields[$relationClass];
                    $sqo->delete()
                    ->restrict($fields[$ownerClass] . '=:ownerId')
                    ->restrict($relatedField . ' IN(:relatedIds)')
                    ->run(array('ownerId' => $ownerId, 'relatedIds' => array_keys($removed)));
                }
                foreach ($added as $entity) {
                    list($relatedClass, $relatedId) = $this->getRelationValue($entity, $key, $idNames);
                    $create->create(array(
                        $fields[$ownerClass] => $ownerId,
                        $fields[$relatedClass] => $relatedId
                    ));
                    $this->loaded[$key][$ownerId][$relatedId] = $entity;
                }
            }
            if ($create->hasData()) {
                $create->run();
            }
        }
        $this->many = array();
    }

    private function symmetrize($entityClass, $entity, $mapperKey, $relationName, $relationClass, $currentEntities)
    {
        $idName = $this->mapper->getIdName($entityClass);
        $accessor = $this->accessor->get(
            $this->accessor->getDeclaringClass($entityClass, $idName)
        );
        $ownerId = $accessor($entity, $idName);
        if (!$ownerId) return;
        if (!isset($this->previous[$mapperKey][$ownerId])) {
            $this->previous[$mapperKey][$ownerId] = array();
        }
        $relatedEntities = $this->indexEntities($relationClass, $currentEntities);
        $removed = array_diff_key($this->previous[$mapperKey][$ownerId], $relatedEntities);
        $added = array_diff_key($relatedEntities, $this->previous[$mapperKey][$ownerId]);
        $this->previous[$mapperKey][$ownerId] = array_diff_key($this->previous[$mapperKey][$ownerId], $removed);
        foreach ($removed as $removedEntity) {
            $this->unlinkInverse($removedEntity, $relationName, $entity);
        }
        foreach ($added as $relatedKey => $addedEntity) {
            if (is_object($addedEntity)) {
                $this->linkInverse($addedEntity, $relationName, $entity);
                $this->previous[$mapperKey][$ownerId][$relatedKey] = $addedEntity;
            }
        }
    }

    private function unlinkInverse($relatedEntity, $relationName, $entity)
    {
        $accessor = $this->getPropertyAccessor($relatedEntity, $relationName);
        if (!$accessor) return;
        $value = $accessor($relatedEntity, $relationName);
        $isArray = is_array($value);
        if (!$isArray && $value !== $entity) {
            return;
        }
        if ($isArray) {
            $index = array_search($entity, $value, true);
            if ($index === false) return;
            array_splice($value, $index, 1);
        } else {
            $value = null;
        }
        $accessor($relatedEntity, $relationName, $value);
        if (!$this->mapper->contains($relatedEntity)) {
            $this->manager->save($relatedEntity);
        }
    }

    private function linkInverse($relatedEntity, $relationName, $entity)
    {
        $accessor = $this->getPropertyAccessor($relatedEntity, $relationName);
        if (!$accessor) return;
        $value = $accessor($relatedEntity, $relationName);
        if (is_array($value)) {
            if (in_array($entity, $value, true)) return;
            array_push($value, $entity);
        } else {
            $value = $entity;
        }
        $accessor($relatedEntity, $relationName, $value);
    }

    private function getPropertyAccessor($entity, $property)
    {
        if (!is_object($entity)) return null;
        $declaringClass = $this->accessor->getDeclaringClass(get_class($entity), $property);
        return $declaringClass ? $this->accessor->get($declaringClass) : null;
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

    private function indexEntities($className, $entities)
    {
        $idName = $this->mapper->getIdName($className);
        $accessor = $this->accessor->get(
            $this->accessor->getDeclaringClass($className, $idName)
        );
        $result = array();
        foreach ($entities as $entity) {
            if (is_object($entity)) {
                $id = $accessor($entity, $idName);
                if (!$id) {
                    $id = spl_object_hash($entity);
                }
            } else {
                $id = $entity;
                $entity = null;
            }
            $result[$id] = $entity;
        }
        return $result;
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
