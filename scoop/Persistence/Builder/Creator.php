<?php

namespace Scoop\Persistence\Builder;

final class Creator
{
    private $query;
    private $values;
    private $connection;
    private $isSubquery = false;
    private $fields;
    private $numFields;
    private $conflictResolver;

    public function __construct($connection, $query, $values, $fields)
    {
        $this->connection = $connection;
        $this->fields = $fields;
        $this->numFields = count($fields);
        if ($values) {
            $this->isSubquery = is_a($values, '\Scoop\Persistence\Builder\Reader') || is_a($values, '\Scoop\Persistence\Builder\Union');
            $this->values = is_array($values) ? $connection->nullify($values) : $values;
        } else {
            $this->values = array();
        }
        $this->query = $this->isSubquery ? substr($query, 0, -7) : $query;
    }

    public function create($values)
    {
        if ($this->isSubquery) {
            throw new \DomainException('INSERT SELECT not support multiple rows');
        }
        $numValues = count($values);
        if ($numValues !== $this->numFields) {
            throw new \InvalidArgumentException(
                'Number of elements incorrect values(' . $numValues . ') and fields(' . $this->numFields . ') for ' . $this->query
            );
        }
        if (array_keys($values) !== range(0, $numValues - 1)) {
            $order = array();
            foreach ($this->fields as $index => $key) {
                $order[$index] = $values[$key];
            }
            $values = $order;
        }
        $this->values = array_merge($this->connection->nullify($values), $this->values);
        return $this;
    }

    public function resolveConflict($columns)
    {
        $this->conflictResolver = new Resolver($this, $this->connection, $columns, $this->fields);
        return $this->conflictResolver;
    }

    public function hasData()
    {
        return !!count($this->values);
    }

    public function run($params = null)
    {
        $statement = $this->connection->prepare($this);
        $this->connection->beginTransaction();
        if ($this->isSubquery) {
            return $statement->execute($params);
        }
        return $statement->execute($this->values);
    }

    public function __toString()
    {
        if ($this->isSubquery) {
            return $this->query . ' ' . $this->values;
        }
        $numRows = count($this->values) / $this->numFields;
        $placeholder = '(' . implode(',', array_fill(0, $this->numFields, '?')) . ')';
        $values = implode(',', array_fill(0, $numRows, $placeholder));
        return $this->query . $values . ($this->conflictResolver ? $this->conflictResolver : '');
    }
}
