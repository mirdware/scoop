<?php

namespace Scoop\Persistence\Entity;

class Query
{
    private $root;
    private $sqo;
    private $fields;
    private $reader;
    private $idName;
    private $collector;
    private $map;
    private $aggregates;

    public function __construct($collector, $aggregate, $map)
    {
        $this->map = $map;
        $this->root = $aggregate;
        $this->collector = $collector;
        $this->sqo = new \Scoop\Persistence\SQO($map['entities'][$aggregate]['table'], 'r');
        $this->fields = $this->getFields($this->root, 'r', false);
        $this->reader = $this->sqo->read($this->fields);
        $this->idName = $collector->getTableId($this->root);
        $this->aggregates = array();
    }

    public function add()
    {
        $aggregates = func_get_args();
        $leftId = 'r.' . $this->idName;
        foreach ($aggregates as $aggregate) {
            $alias = 'a' . count($this->aggregates);
            $this->aggregates[$alias] = $aggregate;
            $rightId = $alias . '.' . $this->collector->getTableId($aggregate);
            $this->reader->join($this->map['entities'][$aggregate]['table'] . ' '.$alias, $leftId . '=' . $rightId);
            $leftId = $rightId;
        }
        return $this;
    }

    public function get($id)
    {
        $this->reader->restrict('r.' . $this->idName . ' = :id');
        $result = $this->reader->run(compact('id'));
        $fields = $this->getFields($this->root, 'r', true);
        $row = $result->fetch();
        return $this->collector->make($this->root, $row['r_' . $this->idName], $row, $fields, $this->sqo);
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
}
