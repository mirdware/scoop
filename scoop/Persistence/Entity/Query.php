<?php

namespace Scoop\Persistence\Entity;

class Query
{
    private $root;
    private $joins;
    private $fields;
    private $mapper;
    private $map;
    private $aggregates;

    public function __construct($mapper, $aggregate, $map)
    {
        $this->map = $map;
        $this->root = $aggregate;
        $this->mapper = $mapper;
        $this->fields = $this->getFields($this->root, 'r', false);
        $this->joins = array();
        $this->aggregates = array();
    }

    public function aggregate()
    {
        $aggregates = func_get_args();
        $left = $this->root;
        $entityMap = $this->map['entities'][$left];
        $leftAlias = 'r';
        $aggregateList = &$this->aggregates;
        $prefix = 'a';
        foreach ($aggregates as $aggregate) {
            $leftId = $this->mapper->getIdName($left);
            $rightId = $this->mapper->getIdName($aggregate);
            if (isset($aggregateList[$aggregate])) {
                $leftAlias = $aggregateList[$aggregate]['alias'];
                $prefix = $leftAlias . 'a';
                $aggregateList = &$aggregateList[$aggregate]['aggregates'];
                continue;
            }
            $rightAlias = $prefix . count($aggregateList);
            $aggregateMap = $this->map['entities'][$aggregate];
            $key = $this->getRelationName($entityMap['relations'], $aggregate);
            $joinType = 'inner';
            $this->fields += $this->getFields($aggregate, $rightAlias, false);
            if (!$key) throw new \UnexpectedValueException('Relation with ' . $aggregate . ' not found');
            if (isset($entityMap['properties'][$key])) {
                $property = $entityMap['properties'][$key];
                if (!empty($property['nullable'])) {
                    $joinType = 'left';
                }
                $columnName = isset($property['column']) ? $property['column'] : $this->toColumn($key);
                $comparation = $leftAlias . '.' . $columnName . '=' . $rightAlias . '.' . $rightId;
            } else {
                $relation = $entityMap['relations'][$key];
                $relationName = $relation[1];
                $joinType = 'left';
                if ($relation[2] === Relation::MANY_TO_MANY) {
                    $relation = explode(':', $relationName);
                    $relation = $this->map['relations'][$relation[1]];
                    $relId = $relation['entities'][$left]['column'];
                    $comparation = $leftAlias . '.' . $leftId . '=' . $leftAlias . $rightAlias . '.' . $relId;
                    $this->joins[] = array($relation['table'] . ' r' . $rightAlias, $comparation, $joinType);
                    $relId = $relation['entities'][$aggregate]['column'];
                    $comparation = $leftAlias . $rightAlias . '.' . $relId . '=' . $rightAlias . '.' . $rightId;
                    $joinType = 'inner';
                } else {
                    $property = $aggregateMap['properties'][$relationName];
                    $columnName = isset($property['column']) ? $property['column'] : $this->toColumn($relationName);
                    $comparation = $leftAlias . '.' . $leftId . '=' . $rightAlias . '.' . $columnName;
                }
            }
            $aggregateList[$aggregate] = array('key' => $key, 'alias' => $rightAlias, 'aggregates' => array());
            $this->joins[] = array($aggregateMap['table'] . ' ' . $rightAlias, $comparation, $joinType);
            $leftAlias = $rightAlias;
            $left = $aggregate;
            $prefix = $leftAlias . 'a';
            $aggregateList = &$aggregateList[$aggregate]['aggregates'];
        }
        return $this;
    }

    public function get($id)
    {
        $reader = $this->createReader();
        $idName = $this->mapper->getTableId($this->root);
        $reader->restrict('r.' . $idName . ' = :id');
        $result = $reader->run(compact('id'));
        $fields = $this->getFields($this->root, 'r', true);
        $rows = $result->fetchAll();
        if (!$rows) return null;
        $aggregateRoot = $this->mapper->make($this->root, $rows[0]['r_' . $idName], $rows[0], $fields);
        $this->assignAggregates($this->root, 'r', $aggregateRoot, $this->aggregates, $rows);
        return $aggregateRoot;
    }

    private function assignAggregates($name, $alias, $entity, $aggregateList, $rows)
    {
        $object = new \ReflectionObject($entity);
        $entityMap = $this->map['entities'][$name];
        $idName = $this->mapper->getTableId($name);
        $row = $this->findRow($alias . '_' . $idName, $object->getProperty($idName)->getValue($entity), $rows);
        foreach ($aggregateList as $name => $relation) {
            $alias = $relation['alias'];
            $fields = $this->getFields($name, $alias, true);
            $idName = $alias . '_' . $this->mapper->getTableId($name);
            $relationType = $entityMap['relations'][$relation['key']][2];
            $isArray = $relationType === Relation::ONE_TO_MANY || $relationType === Relation::MANY_TO_MANY;
            $aggregate = array();
            $id = $row[$idName];
            if (!$id) {
                if (!$isArray) continue;
            } elseif ($isArray) {
                foreach ($rows as $r) {
                    $id = $r[$idName];
                    if (!isset($aggregate[$id])) {
                        $aggregate[$id] = $this->mapper->make($name, $id, $r, $fields);
                        if (!empty($relation['aggregates'])) {
                            $this->assignAggregates($name, $alias, $aggregate[$id], $relation['aggregates'], $rows);
                        }
                    }
                }
                $aggregate = array_values($aggregate);
            } else {
                $aggregate = $this->mapper->make($name, $id, $row, $fields);
                if (!empty($relation['aggregates'])) {
                    $this->assignAggregates($name, $alias, $aggregate, $relation['aggregates'], $rows);
                }
            }
            $prop = $object->getProperty($relation['key']);
            $prop->setAccessible(true);
            $prop->setValue($entity, $aggregate);
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

    private function createReader()
    {
        $sqo = new \Scoop\Persistence\SQO($this->map['entities'][$this->root]['table'], 'r');
        $reader = $sqo->read($this->fields);
        foreach ($this->joins as $join) {
            $reader->join($join[0], $join[1], $join[2]);
        }
        return $reader;
    }

    private function getFields($entity, $table, $isProp)
    {
        $fields = array();
        foreach ($this->map['entities'][$entity]['properties'] as $key => $value) {
            $key = $this->toColumn($key);
            $alias = $table . '_' . $key;
            if (isset($this->map['values'][$value['type']])) {
                if (count($this->map['values'][$value['type']]) > 1) {
                    foreach ($this->map['values'][$value['type']] as $name => $object) {
                        $name = $this->toColumn($name);
                        $columnname = isset($object['column']) ? $object['column'] : $name;
                        $fields[$alias . '$' . $name] = $table . '.' . $key . '_' . $columnname;
                    }
                } else {
                    $fields[$alias . '$value'] =  $table . '.' . $key;
                }
            } else {
                $value = isset($value['column']) ? $value['column'] : $key;
                $fields[$alias] = $isProp ? $key : $table . '.' . $value;
            }
        }
        return $fields;
    }

    private function toColumn($property)
    {
        return strtolower(preg_replace("/([a-z])([A-Z])/", "$1_$2", $property));
    }

    private function getRelationName($relations, $aggregate)
    {
        foreach ($relations as $key => $relation) {
            if ($relation[0] === $aggregate) {
                return $key;
            }
        }
        return null;
    }
}
