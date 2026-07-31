<?php

namespace Scoop\Persistence\Entity;

class Assembler
{
    private $map;
    private $mapper;
    private $accessor;
    private $fieldResolver;

    public function __construct($map, $mapper, $accessor, Resolver\Field $fieldResolver)
    {
        $this->map = $map;
        $this->mapper = $mapper;
        $this->accessor = $accessor;
        $this->fieldResolver = $fieldResolver;
    }

    public function assign($name, $alias, $entity, $aggregateList, $rows)
    {
        $entityMap = $this->map['entities'][$name];
        $idColumn = $this->mapper->getTableId($name);
        $prefix = $alias !== 'r' ? $alias . '$a$' : '';
        $id = $this->getId($entity);
        $row = $this->findRow($prefix . $idColumn, $id, $rows);
        foreach ($aggregateList as $name => $map) {
            $alias = $map['alias'];
            $className = $map['type'];
            $fields = $this->fieldResolver->fieldsFor($className, $alias);
            $prefix = $alias !== 'r' ? $alias . '$a$' : '';
            $idColumn = $prefix . $this->mapper->getTableId($className);
            $relationType = $entityMap['relations'][$name][2];
            $isArray = $relationType === Relation::ONE_TO_MANY || $relationType === Relation::MANY_TO_MANY;
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
                $value = array_values($value);
            } else {
                $value = $this->mapper->make($className, $id, $row, $fields);
                if (!empty($map['aggregates'])) {
                    $this->assign($className, $alias, $value, $map['aggregates'], $rows);
                }
            }
            $className = $this->accessor->getDeclaringClass(get_class($entity), $name);
            if (!$className) continue;
            $this->accessor->get($className)($entity, $name, $value);
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
        return $this->accessor->get($className)($entity, $idName);
    }
}
