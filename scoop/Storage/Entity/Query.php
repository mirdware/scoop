<?php
namespace Scoop\Storage\Entity;

class Query
{
    private $classEntity;
    private $map;
    private $sqo;
    private $collector;

    public function __construct($collector, $classEntity, $map)
    {
        $this->classEntity = $classEntity;
        $this->map = $map;
        $this->collector = $collector;
        $this->sqo = new \Scoop\Storage\SQO($map[$classEntity]['table']);
    }

    public function get()
    {
        $fields = array();
        foreach ($this->map[$this->classEntity]['fields'] as $key => $value) {
            $fields[isset($value['name']) ? $value['name'] : $key] = $key;
        }
        $result = $this->sqo->read(array_keys($fields))->run();
        $entities = array();
        while ($row = $result->fetch()) {
            $id = isset($this->map[$this->classEntity]['id']) ? $row[$this->map[$this->classEntity]['id']] : $row['id'];
            $entities[] = $this->collector->make($this->classEntity, $id, $row, $fields, $this->sqo);
        }
        return $entities;
    }
}