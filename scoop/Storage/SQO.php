<?php
namespace Scoop\Storage;

class SQO
{
    const READ = 1;
    const UPDATE = 2;
    const DELETE = 3;
    private $table;
    private $aliasTable;
    private $con;

    public function __construct($table, $alias = '', $connection = null)
    {
        $this->table = $table;
        $this->aliasTable = $table.' '.$alias;
        $this->con = $connection === null ? \Scoop\Context::connect() : $connection;
    }

    public function create($fields = null, SQO\Reader $select = null)
    {
        $query = 'INSERT INTO '.$this->table;
        return new SQO\Factory($query, $fields, $select, $this->con);
    }

    public function read()
    {
        $args = func_get_args();
        $fields = isset($args[0]) ? implode(',', self::getFields($args)) : '*';
        $query = 'SELECT '.$fields.' FROM '.$this->aliasTable;
        return new SQO\Reader($query, SQO::READ, array(), $this->con);
    }

    public function update($fields)
    {
        $operators = array('+', '-', '/', '*', '%');
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
        return new SQO\Filter(substr($query, 0, -2), SQO::UPDATE, $fields, $this->con);
    }

    public function delete()
    {
        $query = 'DELETE FROM '.$this->table;
        return new SQO\Filter($query, SQO::DELETE, array(), $this->con);
    }

    public function getLastId($nameSeq = null)
    {
        return $this->con->lastInsertId($nameSeq);
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
