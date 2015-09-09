<?php
namespace Scoop\Storage\SQO;

final class Factory
{
    private $query;
    private $keys;
    private $con;

    public function __construct($query, &$keys, &$connexion)
    {
        $this->query = $query;
        $this->keys = &$keys;
        $this->con = &$connexion;
    }

    public function join($fields)
    {
        ksort($fields);
        array_walk($fields, array($this, 'quote'), $this->con);
        if (!array_diff($this->keys, array_keys($fields))) {
            $this->query .= ', ('.implode(', ', $fields).')';
        }
        return $this;
    }

    public function run()
    {
        return $this->con->exec($this);
    }

    public function __toString()
    {
        return $this->query;
    }

    public static function quote(&$value, $key, &$con)
    {
        if (is_array($value)) {
            $value = str_replace('?', $con->quote($value[1]), $value[0]);
        } elseif (is_object($value) &&
            $value instanceof Result &&
            $value->getType() === \Scoop\Storage\SQO::READ) {
            $value = '('.$value.') ';
        } else {
            $value = $con->quote($value);
        }
        return $value;
    }
}
