<?php
namespace Scoop\Storage\SQO;

final class Factory
{
    private $query = '';
    private $con;
    private $values = array();
    private $keys = array();

    public function __construct($query, $keys, $values, $connexion)
    {
        $this->query = $query;
        $this->con = $connexion;
        $this->keys = $keys;
        $this->insert($values);
    }

    public function insert($fields)
    {
        ksort($fields);
        $keys = array_keys($fields);
        if (array_diff($this->keys, $keys)) {
            throw new \UnexpectedValueException('Keys ['.implode(',', $keys).'] unsupported');
        }
        $this->values = array_merge(array_values($fields), $this->values);
        return $this;
    }

    public function run()
    {
        $statement = $this->con->prepare($this);
        return $statement->execute($this->values);
    }

    public function __toString()
    {
        $numFields = count($this->keys);
        $numRows = count($this->values)/$numFields;
        $placeholder = '('.implode(',', array_fill(0, $numFields, '?')).')';
        $values = implode(',', array_fill(0, $numRows, $placeholder));
        return $this->query.$values;
    }
}
