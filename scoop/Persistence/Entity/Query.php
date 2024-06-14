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
            $this->fields += $this->getFields($aggregate, $alias, false);
            if (!$key) throw new \Exception('Relation with ' . $aggregate . ' not found');
            if (isset($entityMap['properties'][$key])) {
                $property = $entityMap['properties'][$key];
                $comparation = 'r.' . (isset($property['name']) ? $property['name'] : $key) . '=' . $alias . '.' . $this->collector->getIdName($aggregate);
            } else {
                $relationName = $entityMap['relations'][$key][1];
                $property = $aggregateMap['properties'][$relationName];
                $comparation = 'r.' . $this->collector->getIdName($aggregate) . '=' . $alias . '.' . (isset($property['name']) ? $property['name'] : $relationName);
            }
            $this->aggregates[$alias] = $key;
            $this->joins[] = array($aggregateMap['table'] . ' ' . $alias, $comparation);
        }
        return $this;
    }

    public function get($id)
    {
        $idName = $this->collector->getTableId($this->root);
        $sqo = new \Scoop\Persistence\SQO($this->map['entities'][$this->root]['table'], 'r');
        $reader = $sqo->read($this->fields);
        foreach ($this->joins as $join) {
            $reader->join($join[0], $join[1]);
        }
        $reader->restrict('r.' . $idName . ' = :id');
        echo $reader->bind(compact('id'));
        $result = $reader->run(compact('id'));
        $fields = $this->getFields($this->root, 'r', true);
        $row = $result->fetch();
        if (!$row) return null;
        return $this->collector->make($this->root, $row['r_' . $idName], $row, $fields, $sqo);
    }

    private function getFields($entity, $alias, $isProp)
    {
        $fields = array();
        foreach ($this->map['entities'][$entity]['properties'] as $key => $value) {
            $value = isset($value['name']) ? $value['name'] : $key;
            $fields[$alias . '_' . $key] = $isProp ? $key : $alias . '.' . $value;
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
