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
        $this->isSubquery = is_a($table, '\Scoop\Persistence\SQO\Reader') || is_a($table, '\Scoop\Persistence\SQO\Union');
        $this->table = $this->isSubquery ? '(' . $table . ')' : $table;
        $this->aliasTable = $this->table . ' ' . $alias;
        $this->connection = $connection;
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
        $query .= ' (' . implode(',', $fields) . ') VALUES ';
        return new SQO\Creator($query, $values, $fields, $this);
    }

    public function read()
    {
        $args = func_get_args();
        $fields = isset($args[0]) ? implode(',', self::getFields($args)) : '*';
        $query = 'SELECT ' . $fields . ' FROM ' . $this->aliasTable;
        return new SQO\Reader($query, $this, $this->connection);
    }

    public function update($fields)
    {
        if ($this->isSubquery) {
            throw new \DomainException('Subquery on SET clausule not support UPDATE');
        }
        $operators = array('+', '-', '/', '*', '%');
        $fields = $this->nullify($fields);
        $query = 'UPDATE ' . $this->table . ' SET ';
        foreach ($fields as $key => $value) {
            $lastChar = substr($key, -1);
            if (in_array($lastChar, $operators)) {
                unset($fields[$key]);
                $key = substr($key, 0, -1);
                $fields[$key] = $value;
                $query .= $key . ' = ' . $key . ' ' . $lastChar . ' :' . $key . ', ';
            } else {
                $query .= $key . ' = :' . $key . ', ';
            }
        }
        return new SQO\Criteria(substr($query, 0, -2), self::UPDATE, $this, $fields);
    }

    public function delete()
    {
        if ($this->isSubquery) {
            throw new \DomainException('Subquery on FROM clausule not support DELETE');
        }
        $query = 'DELETE FROM ' . $this->table;
        return new SQO\Criteria($query, self::DELETE, $this);
    }

    public function getLastId($nameSeq = null)
    {
        return $this->getConnection()->lastInsertId($nameSeq);
    }

    public function getConnection()
    {
        if (is_string($this->connection)) {
            $this->connection = \Scoop\Context::connect($this->connection);
        }
        return $this->connection;
    }

    public function nullify($parameters)
    {
        $result = array();
        foreach ($parameters as $key => $value) {
            $result[$key] = $value === '' ? null : $value;
        }
        return $result;
    }

    private static function getFields($args)
    {
        if (is_array($args[0])) {
            $args = $args[0];
        }
        foreach ($args as $key => &$value) {
            if ($value instanceof SQO\Reader) {
                $value = '(' . $value . ')';
            }
            if (!is_numeric($key)) {
                $value .= ' AS ' . $key;
            }
        }
        return $args;
    }
}
