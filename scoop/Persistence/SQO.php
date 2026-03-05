<?php

namespace Scoop\Persistence;

/**
 * @deprecated since version 0.8, use Scoop\Persistence\Builder instead
 */
class SQO
{
    const READ = 1;
    const UPDATE = 2;
    const DELETE = 3;
    private $table;
    private $aliasTable;
    private $connection;
    private $isSubquery;

    public function __construct($table, $alias = '', $connection = 'default')
    {
        $this->connection = is_string($connection) ? \Scoop\Context::connect($connection) : $connection;
        if ($alias !== '') {
            $alias = $this->connection->quoteColumn($alias);
        }
        $this->isSubquery = is_a($table, '\Scoop\Persistence\Builder\Reader') || is_a($table, '\Scoop\Persistence\Builder\Union');
        $this->table = $this->isSubquery ? '(' . $table . ')' : $this->connection->quoteColumn($table);
        $this->aliasTable = $this->table . ' ' . $alias;
    }

    public function create($fields, $select = null)
    {
        if ($this->isSubquery) {
            throw new \DomainException('Subquery on INTO clausule not support CREATE');
        }
        $query = 'INSERT INTO ' . $this->table;
        $values = $select;
        if (array_keys($fields) !== range(0, count($fields) - 1)) {
            $values = array_values($fields);
            $fields = array_keys($fields);
        }
        $fields = array_map(array($this->connection, 'quoteColumn'), $fields);
        $query .= ' (' . implode(',', $fields) . ') VALUES ';
        return new Builder\Creator($this->connection, $query, $values, $fields);
    }

    public function read()
    {
        $args = func_get_args();
        $fields = isset($args[0]) ? implode(',', $this->getFields($args)) : '*';
        $query = 'SELECT ' . $fields . ' FROM ' . $this->aliasTable;
        return new Builder\Reader($this->connection, $query);
    }

    public function update($fields)
    {
        if ($this->isSubquery) {
            throw new \DomainException('Subquery on SET clausule not support UPDATE');
        }
        $operators = array('+', '-', '/', '*', '%');
        $fields = $this->connection->nullify($fields);
        $query = 'UPDATE ' . $this->table . ' SET ';
        foreach ($fields as $key => $value) {
            $lastChar = substr($key, -1);
            if (in_array($lastChar, $operators)) {
                unset($fields[$key]);
                $key = substr($key, 0, -1);
                $fields[$key] = $value;
                $column = $this->connection->quoteColumn($key);
                $query .= $column . ' = ' . $column . ' ' . $lastChar . ' :' . $key . ', ';
            } else {
                $query .= $this->connection->quoteColumn($key) . ' = :' . $key . ', ';
            }
        }
        return new Builder\Criteria($this->connection, substr($query, 0, -2), self::UPDATE, $fields);
    }

    public function delete()
    {
        if ($this->isSubquery) {
            throw new \DomainException('Subquery on FROM clausule not support DELETE');
        }
        $query = 'DELETE FROM ' . $this->table;
        return new Builder\Criteria($this->connection, $query, self::DELETE);
    }

    public function getLastId($nameSeq = null)
    {
        return $this->connection->lastInsertId($nameSeq);
    }

    public function getConnection()
    {
        return $this->connection;
    }

    private function getFields($args)
    {
        if (is_array($args[0])) {
            $args = $args[0];
        }
        foreach ($args as $key => &$value) {
            if ($value instanceof Builder\Reader) {
                $value = '(' . $value . ')';
            } else {
                $value = $this->connection->quoteColumn($value, true);
                if (!is_numeric($key)) {
                    $value .= ' AS ' . $this->connection->quoteColumn($key);
                }
            }
        }
        return $args;
    }
}
