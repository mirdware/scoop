<?php

namespace Scoop\Persistence\Entity\Query;

class Assembler
{
    private $map;
    private $mapper;
    private $accessor;
    private $fieldResolver;
    private $relation;
    private $fields = array();

    public function __construct($map, $mapper, $accessor, $fieldResolver, $relation)
    {
        $this->map = $map;
        $this->mapper = $mapper;
        $this->accessor = $accessor;
        $this->fieldResolver = $fieldResolver;
        $this->relation = $relation;
    }

    public function assign($name, $alias, $entity, $aggregateList, $rows)
    {
        $entityMap = $this->map['entities'][$name];
        $idColumn = $this->mapper->getTableId($name);
        $prefix = $alias !== 'r' ? $alias . '$a$' : '';
        $idOwner = $this->getId($entity);
        $row = $this->findRow($prefix . $idColumn, $idOwner, $rows);
        foreach ($aggregateList as $name => $map) {
            $alias = $map['alias'];
            $className = $map['type'];
            $fieldKey = $className . ':' . $alias;
            if (!isset($this->fields[$fieldKey])) {
                $this->fields[$fieldKey] = $this->fieldResolver->fieldsFor($className, $alias);
            }
            $fields = $this->fields[$fieldKey];
            $prefix = $alias !== 'r' ? $alias . '$a$' : '';
            $idColumn = $prefix . $this->mapper->getTableId($className);
            $relation = $entityMap['relations'][$name];
            $relationType = $relation[2];
            $isArray = $relationType === \Scoop\Persistence\Entity\Relation::ONE_TO_MANY ||
            $relationType === \Scoop\Persistence\Entity\Relation::MANY_TO_MANY;
            $value = array();
            $id = $row[$idColumn];
            if (!$id) {
                if (!$isArray) continue;
            } elseif ($isArray) {
                foreach ($rows as $r) {
                    $id = $r[$idColumn];
                    if (!isset($value[$id])) {
                        $value[$id] = $this->mapper->make($className, $id, $r, $fields);
                        if (!empty($map['aggregates'])) {
                            $this->assign($className, $alias, $value[$id], $map['aggregates'], $rows);
                        }
                    }
                }
                if ($relationType === \Scoop\Persistence\Entity\Relation::MANY_TO_MANY) {
                    $this->relation->track($relation[1], $idOwner, $value);
                }
                $value = array_values($value);
            } else {
                $value = $this->mapper->make($className, $id, $row, $fields);
                if (!empty($map['aggregates'])) {
                    $this->assign($className, $alias, $value, $map['aggregates'], $rows);
                }
            }
            $className = $this->accessor->getDeclaringClass(get_class($entity), $name);
            if (!$className) continue;
            $propertyAccessor = $this->accessor->get($className);
            $propertyAccessor($entity, $name, $value);
        }
    }

    private function findRow($idName, $id, $rows)
    {
        foreach ($rows as $row) {
            if ($row[$idName] === $id) {
                return $row;
            }
        }
    }

    private function getId($entity)
    {
        $className = get_class($entity);
        while ($parent = get_parent_class($className)) {
            $className = $parent;
        }
        $idName = $this->mapper->getIdName($className);
        $accessor = $this->accessor->get($className);
        return $accessor($entity, $idName);
    }
}
