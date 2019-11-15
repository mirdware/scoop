<?php
namespace Scoop\Storage\SQO;

final class Factory
{
    private $query;
    private $values;
    private $con;
    private $isReader;

    public function __construct($query, $values, $numFields, $connection)
    {
        $this->query = $query;
        $this->con = $connection;
        $this->numFields = $numFields;
        $this->values = $values ? $values : array();
        $this->isReader = is_a($values, '\Scoop\Storage\SQO\Filter');
    }

    public function create($values)
    {
        if ($this->isReader) {
            throw new \DomainException('INSERT SELECT not support multiple rows');
        }
        if (count($values) !== $this->numFields) {
            throw new \InvalidArgumentException('Number of elements incorrect');
        }
        $this->values = array_merge($values, $this->values);
        return $this;
    }

    public function run($params = null)
    {
        $statement = $this->con->prepare($this);
        if ($this->isReader) {
            return $statement->execute($params);
        }
        return $statement->execute($this->values);
    }

    public function __toString()
    {
        if ($this->isReader) {
            return $this->query.' '.$this->values;
        }
        $numRows = count($this->values)/$this->numFields;
        $placeholder = '('.implode(',', array_fill(0, $this->numFields, '?')).')';
        $values = implode(',', array_fill(0, $numRows, $placeholder));
        return $this->query.$values;
    }
}
