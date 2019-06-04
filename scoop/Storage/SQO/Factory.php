<?php
namespace Scoop\Storage\SQO;

final class Factory
{
    private $query = '';
    private $values = array();
    private $keys = null;
    private $select;
    private $con;

    public function __construct($query, $values, $select, $connection)
    {
        $this->query = $query;
        $this->con = $connection;
        $this->select = $select;
        if ($values) {
            $select ? $this->createInsertSelect($values) : $this->create($values);
        }
    }

    public function create($fields)
    {
        if ($this->select) {
            throw new \DomainException('INSERT SELECT not support multiple rows');
        }
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

    public function run($params = array())
    {
        if ($this->keys !== null) {
            foreach ($params AS $key => $value) {
                $this->select->bindParam($key, $value);
            }
            $statement = $this->con->prepare($this);
            return $statement->execute($this->values + $params);
        }
        throw new \DomainException('the SQO expression for create does not have rows');
    }

    public function __toString()
    {
        if ($this->select) {
            return $this->query.' '.$this->select;
        }
        $numFields = count($this->keys);
        $numRows = count($this->values)/$numFields;
        $placeholder = '('.implode(',', array_fill(0, $numFields, '?')).')';
        $values = implode(',', array_fill(0, $numRows, $placeholder));
        return $this->query.$values;
    }

    private function createInsertSelect($values)
    {
        $this->query .= ' ('.implode(',', $values).')';
        $this->keys = $values;
    }
}
