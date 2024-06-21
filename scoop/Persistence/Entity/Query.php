<?php

namespace Scoop\Persistence\Entity;

class Query
{
    private $root;
    private $joins;
    private $fields;
    private $collector;
    private $map;
    private $aggregates;

    public function __construct($collector, $aggregate, $map)
    {
        $this->map = $map;
        $this->root = $aggregate;
        $this->collector = $collector;
        $this->fields = $this->getFields($this->root, 'r', false);
        $this->joins = array();
        $this->aggregates = array();
    }

    public function aggregate()
    {
        $aggregates = func_get_args();
        $entityMap = $this->map['entities'][$this->root];
        foreach ($aggregates as $aggregate) {
            $alias = 'a' . count($this->aggregates);
            $aggregateMap = $this->map['entities'][$aggregate];
            $key = $this->getRelationName($entityMap['relations'], $aggregate);
            $joinType = 'inner';
            $this->fields += $this->getFields($aggregate, $alias, false);
            if (!$key) throw new \UnexpectedValueException('Relation with ' . $aggregate . ' not found');
            if (isset($entityMap['properties'][$key])) {
                $property = $entityMap['properties'][$key];
                if (!empty($property['nullable'])) {
                    $joinType = 'left';
                }
                $comparation = 'r.' . (isset($property['name']) ? $property['name'] : $key) . '=' . $alias . '.' . $this->collector->getIdName($aggregate);
            } else {
                $relation = $entityMap['relations'][$key];
                $relationName = $relation[1];
                $joinType = 'left';
                if ($relation[2] === Relation::MANY_TO_MANY) {
                    $relation = explode(':', $relationName);
                    $relation = $this->map['relations'][$relation[1]];
                    $comparation = 'r.' . $this->collector->getIdName($this->root) . '=r' . $alias . '.' . $relation['entities'][$this->root]['column'];
                    $this->joins[] = array($relation['table'] . ' r' . $alias, $comparation, $joinType);
                    $comparation = 'r' . $alias . '.' . $relation['entities'][$aggregate]['column'] . '=' . $alias . '.' . $this->collector->getIdName($this->root);
                } else {
                    $property = $aggregateMap['properties'][$relationName];
                    $comparation = 'r.' . $this->collector->getIdName($this->root) . '=' . $alias . '.' . (isset($property['name']) ? $property['name'] : $relationName);
                }
            }
            $this->aggregates[$alias] = compact('key', 'aggregate');
            $this->joins[] = array($aggregateMap['table'] . ' ' . $alias, $comparation, $joinType);
        }
        return $this;
    }

    public function get($id)
    {
        $idName = $this->collector->getTableId($this->root);
        $sqo = new \Scoop\Persistence\SQO($this->map['entities'][$this->root]['table'], 'r');
        $reader = $sqo->read($this->fields);
        foreach ($this->joins as $join) {
            $reader->join($join[0], $join[1], $join[2]);
        }
        $reader->restrict('r.' . $idName . ' = :id');
        echo $reader;
        $result = $reader->run(compact('id'));
        $fields = $this->getFields($this->root, 'r', true);
        $row = $result->fetchAll();
        if (!$row) return null;
        $aggregateRoot = $this->collector->make($this->root, $row[0]['r_' . $idName], $row[0], $fields);
        $object = new \ReflectionObject($aggregateRoot);
        $entityMap = $this->map['entities'][$this->root];
        foreach ($this->aggregates as $alias => $relation) {
            $fields = $this->getFields($relation['aggregate'], $alias, true);
            $idName = $this->collector->getTableId($relation['aggregate']);
            $relationType = $entityMap['relations'][$relation['key']][2];
            $isArray = $relationType === Relation::ONE_TO_MANY || $relationType === Relation::MANY_TO_MANY;
            $aggregate = array();
            $id = $row[0][$alias . '_' . $idName];
            if (!$id) {
                if (!$isArray) continue;
            } elseif ($isArray) {
                foreach ($row as $r) {
                    $id = $r[$alias . '_' . $idName];
                    $aggregate[$id] = $this->collector->make($relation['aggregate'], $id, $r, $fields);
                }
                $aggregate = array_values($aggregate);
            } else {
                $aggregate = $this->collector->make($relation['aggregate'], $id, $row[0], $fields);
            }
            $prop = $object->getProperty($relation['key']);
            $prop->setAccessible(true);
            $prop->setValue($aggregateRoot, $aggregate);
        }
        return $aggregateRoot;
    }

    private function getFields($entity, $alias, $isProp)
    {
        $fields = array();
        foreach ($this->map['entities'][$entity]['properties'] as $key => $value) {
            if (isset($this->map['values'][$value['type']])) {
                if (count($this->map['values'][$value['type']]) > 1) {
                    foreach ($this->map['values'][$value['type']] as $name => $object) {
                        $fields[$alias . 'vo_' . $key . '$' . $name] = $alias . '.' . (isset($object['name']) ? $object['name'] : $key . '_' . $name);
                    }
                } else {
                    $fields[$alias . 'vo_' . $key . '$value'] =  $alias . '.' .$key;
                }
            } else {
                $value = isset($value['name']) ? $value['name'] : $key;
                $fields[$alias . '_' . $key] = $isProp ? $key : $alias . '.' . $value;
            }
        }
        return $fields;
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
