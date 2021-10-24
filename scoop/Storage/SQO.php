<?php
namespace Scoop\Storage;

class SQO
{
    const READ = 1;
    const UPDATE = 2;
    const DELETE = 3;
    private $table;
    private $aliasTable;
    private $connectionName;

    public function __construct($table, $alias = '', $connectionName = 'default')
    {
        $this->table = $table;
        $this->aliasTable = $table.' '.$alias;
        $this->connectionName = $connectionName;
    }

    public function create($fields, SQO\Reader $select = null)
    {
        $query = 'INSERT INTO '.$this->table;
        $values = $select;
        if (array_keys($fields) !== range(0, count($fields) - 1)) {
            $values = array_values($fields);
            $fields = array_keys($fields);
        }
        $query .= ' ('.implode(',', $fields).') VALUES ';
        return new SQO\Factory($query, $values, $fields, $this);
    }

    public function read()
    {
        $args = func_get_args();
        $fields = isset($args[0]) ? implode(',', self::getFields($args)) : '*';
        $query = 'SELECT '.$fields.' FROM '.$this->aliasTable;
        return new SQO\Reader($query, $this);
    }

    public function update($fields)
    {
        $operators = array('+', '-', '/', '*', '%');
        $fields = $this->nullify($fields);
        $query = 'UPDATE '.$this->table.' SET ';
        foreach ($fields as $key => $value) {
            $lastChar = substr($key, -1);
            if (in_array($lastChar, $operators)) {
                unset($fields[$key]);
                $key = substr($key, 0, -1);
                $fields[$key] = $value;
                $query .= $key.' = '.$key.' '.$lastChar.' :'.$key.', ';
            } else {
                $query .= $key.' = :'.$key.', ';
            }
        }
        return new SQO\Filter(substr($query, 0, -2), self::UPDATE, $this);
    }

    public function delete()
    {
        $query = 'DELETE FROM '.$this->table;
        return new SQO\Filter($query, self::DELETE, $this);
    }

    public function getLastId($nameSeq = null)
    {
        return $this->getConnection()->lastInsertId($nameSeq);
    }

    public function getConnection()
    {
        return \Scoop\Context::connect($this->connectionName);
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
                $value = '('.$value.')';
            }
            if (!is_numeric($key)) {
                $value .= ' AS '.$key;
            }
        }
        return $args;
    }
}
