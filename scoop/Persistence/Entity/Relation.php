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
    private $many = array();
    private $loaded = array();
    private $previous = array();

    public function __construct($map, $mapper, $manager, $accessor)
    {
        $this->relationMap = $map;
        $this->mapper = $mapper;
        $this->manager = $manager;
        $this->accessor = $accessor;
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
            if (is_array($relationEntity)) {
                if ($mapperKey !== null) {
                    if (!isset($this->many[$mapperKey])) {
                        $this->many[$mapperKey] = new \SplObjectStorage();
                    }
                    $this->many[$mapperKey][$entity] = $relationEntity;
                    $this->detachRemoved($entityName, $entity, $mapperKey, $relationName, $relation[0], $relationEntity);
                }
                foreach ($relationEntity as $e) {
                    if (!$this->mapper->contains($e)) {
                        $this->manager->save($e);
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
        $this->many =array();
    }

    private function detachRemoved($entityClass, $entity, $mapperKey, $relationName, $relationClass, $entities)
    {
        $idName = $this->mapper->getIdName($entityClass);
        $accessor = $this->accessor->get(
            $this->accessor->getDeclaringClass($entityClass, $idName)
        );
        $ownerId = $accessor($entity, $idName);
        if (!isset($this->previous[$mapperKey][$ownerId])) {
            $this->previous[$mapperKey][$ownerId] = array();
        }
        $classDeclaring = $this->accessor->getDeclaringClass($relationClass, $relationName);
        if (!$classDeclaring) return;
        $accessor = $this->accessor->get($classDeclaring);
        $relatedEntities = $this->indexEntities($relationClass, $entities);
        $removed = array_diff_key($this->previous[$mapperKey][$ownerId], $relatedEntities);
        $added = array_diff_key($relatedEntities, $this->previous[$mapperKey][$ownerId]);
        $this->previous[$mapperKey][$ownerId] = array_diff_key($this->previous[$mapperKey][$ownerId], $removed);
        foreach ($removed as $removedEntity) {
            $value = $accessor($removedEntity, $relationName);
            $index = array_search($entity, $value, true);
            if ($index !== false) {
                array_splice($value, $index, 1);
                $accessor($removedEntity, $relationName, $value);
            }
        }
        foreach ($added as $entityId => $addedEntity) {
            $value = $accessor($addedEntity, $relationName);
            array_push($value, $entity);
            $accessor($addedEntity, $relationName, $value);
            $this->previous[$mapperKey][$ownerId][$entityId] = $addedEntity;
        }
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
            $id = $accessor($entity, $idName);
            if ($id) {
                $result[$id] = $entity;
            } else {
                $result[spl_object_hash($entity)] = $entity;
            }
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
