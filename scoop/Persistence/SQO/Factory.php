<?php

namespace Scoop\Persistence\SQO;

final class Factory
{
    private $query;
    private $values;
    private $sqo;
    private $isReader;
    private $fields;
    private $numFields;

    public function __construct($query, $values, $fields, $sqo)
    {
        $this->sqo = $sqo;
        $this->fields = $fields;
        $this->numFields = count($fields);
        if ($values) {
            $this->isReader = is_a($values, '\Scoop\Persistence\SQO\Reader');
            $this->values = is_array($values) ? $sqo->nullify($values) : $values;
        } else {
            $this->values = array();
        }
        $this->query = $this->isReader ? substr($query, 0, -7) : $query;
    }

    public function create($values)
    {
        if ($this->isReader) {
            throw new \DomainException('INSERT SELECT not support multiple rows');
        }
        if (count($values) !== $this->numFields) {
            throw new \InvalidArgumentException('Number of elements incorrect');
        }
        if (array_keys($values) !== range(0, count($values) - 1)) {
            $order = array();
            foreach ($this->fields as $index => $key) {
                $order[$index] = $values[$key];
            }
            $values = $order;
        }
        $this->values = array_merge($this->sqo->nullify($values), $this->values);
        return $this;
    }

    public function hasData()
    {
        return !!count($this->values);
    }

    public function run($params = null)
    {
        $con = $this->sqo->getConnection();
        $statement = $con->prepare($this);
        $con->beginTransaction();
        if ($this->isReader) {
            return $statement->execute($params);
        }
        return $statement->execute($this->values);
    }

    public function __toString()
    {
        if ($this->isReader) {
            return $this->query . ' ' . $this->values;
        }
        $numRows = count($this->values) / $this->numFields;
        $placeholder = '(' . implode(',', array_fill(0, $this->numFields, '?')) . ')';
        $values = implode(',', array_fill(0, $numRows, $placeholder));
        return $this->query . $values;
    }
}
