<?php
namespace Scoop\Storage;

class SQO
{
    private $table;
    private $aliasTable;
    private $con;
    const READ = 1;
    const UPDATE = 2;
    const DELETE = 3;

    public function __construct($table, $alias = '', $connexion = null)
    {
        $this->table = $table;
        $this->aliasTable = $table.' '.$alias;
        $this->con = $connexion === null? DBC::get(): $connexion;
    }

    public function create($fields)
    {
        $keys = array_keys($fields);
        sort($keys);
        $query = 'INSERT INTO '.$this->table.' ('.implode(',', $keys).') VALUES ';
        return new SQO\Factory($query, $keys, $fields, $this->con);
    }

    public function read()
    {
        $args = func_get_args();
        $fields = '*';
        if (isset($args[0])) {
            if (is_array($args[0])) {
                $args = $args[0];
            }
            foreach ($args as $key => &$value) {
                if ($value instanceof SQO\Result && $value->getType() === SQO::READ) {
                    $value = '('.$value.')';
                }
                if (!is_numeric($key)) {
                    $value .= ' AS '.$key;
                }
            }
            $fields = implode(',', $args);
        }
        $query = 'SELECT '.$fields.' FROM '.$this->aliasTable;
        return new SQO\Expander($query, SQO::READ, array(), $this->con);
    }

    public function update($fields)
    {
        $operators = array('+', '-', '/', '*', '%');
        $query = 'UPDATE '.$this->table.' SET ';
        foreach ($fields as $key => &$value) {
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

    public function getLastId($nameSeq)
    {
        return $this->con->lastInsertId($nameSeq);
    }
}
