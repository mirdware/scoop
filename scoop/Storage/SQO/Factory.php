<?php
namespace Scoop\Storage\SQO;

final class Factory
{
    private $query = '';
    private $con;
    private $values = array();
    private $keys = null;

    public function __construct($query, $values, $connexion)
    {
        $this->query = $query;
        $this->con = $connexion;
        if ($values) {
            $this->create($values);
        }
    }

    public function create($fields)
    {
        ksort($fields);
        $keys = array_keys($fields);
        if (!$this->keys) {
            $this->keys = $keys;
            $this->query .= ' ('.implode(',', $keys).') VALUES ';
        } else if (array_diff($this->keys, $keys)) {
            throw new \UnexpectedValueException('Keys ['.implode(',', $keys).'] unsupported');
        }
        $this->values = array_merge(array_values($fields), $this->values);
        return $this;
    }

    public function run()
    {
        if ($this->keys !== null) {
            $statement = $this->con->prepare($this);
            return $statement->execute($this->values);
        }
        throw new \DomainException('the SQO expression for create does not have rows');
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
