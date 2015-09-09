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

    public static function exec() {
        $args = func_get_args();
        $exec = '';

        foreach ($args as &$sqo) {
            if (!($sqo instanceof SQO\Result)) {
                throw new \Exception('one parameter sent is not a valid SQO');
            }
            if (!isset($connexion)) {
                $connexion = $sqo->getConnexion();
            } elseif ($connexion !== $sqo->getConnexion()) {
                throw new \Exception('you can not run on different connections');
            }
            $exec .= $sqo.';';
        }
        return $connexion->exec($exec);
    }

    public function create($fields)
    {
        ksort($fields);
        array_walk($fields, array('\Scoop\Storage\SQO\Factory', 'quote'), $this->con);
        $keys = array_keys($fields);
        $query = 'INSERT INTO '.$this->table.' ('.implode(', ', $keys).
            ') VALUES ('.implode(', ', $fields).')';

        return new SQO\Factory($query, $keys, $this->con);
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
                if (is_object($value) && $value instanceof SQO\Result &&
                    $value->getType() === SQO::READ) {
                    $value = '('.$value.')';
                }
                if (!is_numeric($key)) {
                    $value .= ' AS '.$key;
                }
            }
            $fields = implode(', ', $args);
        }
        $query = 'SELECT '.$fields.' FROM '.$this->aliasTable;
        return new SQO\Result($query, self::READ, $this->con);
    }

    public function update($fields)
    {
        array_walk($fields, array('\Scoop\Storage\SQO\Factory', 'quote'), $this->con);
        $query = 'UPDATE '.$this->aliasTable.' SET ';

        foreach ($fields as $key => &$value) {
            $query .= $key.' = '.$value.', ';
        }
        return new SQO\Result(substr($query, 0, -2), self::UPDATE, $this->con);
    }

    public function delete()
    {
        $query = 'DELETE FROM '.$this->aliasTable;
        return new SQO\Result($query, self::DELETE, $this->con);
    }

    public function flush()
    {
        $this->con->commit();
        $this->con->beginTransaction();
    }
}
