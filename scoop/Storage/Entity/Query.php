<?php

namespace Scoop\Storage\Entity;

class Query extends Mapper
{
    private $classEntity;
    private $sqo;
    private $collector;

    public function __construct($collector, $classEntity, $map)
    {
        parent::__construct($map);
        $this->classEntity = $classEntity;
        $this->collector = $collector;
        $this->sqo = new \Scoop\Storage\SQO($map[$classEntity]['table']);
    }

    public function get()
    {
        $fields = array();
        foreach ($this->map[$this->classEntity]['properties'] as $key => $value) {
            $fields[isset($value['name']) ? $value['name'] : $key] = $key;
        }
        $result = $this->sqo->read(array_keys($fields))->run();
        $entities = array();
        while ($row = $result->fetch()) {
            $id = $this->getIdName($this->classEntity);
            $entities[] = $this->collector->make($this->classEntity, $row[$id], $row, $fields, $this->sqo);
        }
        return $entities;
    }
}
