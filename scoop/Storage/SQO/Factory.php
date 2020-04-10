<?php
namespace Scoop\Storage\SQO;

final class Factory
{
    private $query;
    private $values;
    private $con;
    private $isReader;
    private $fields;
    private $numFields;

    public function __construct($query, $values, $fields, $connection)
    {
        $this->query = $query;
        $this->con = $connection;
        $this->fields = $fields;
        $this->numFields = count($fields);
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
        if (array_keys($values) !== range(0, count($values) - 1)) {
            $order = array();
            foreach ($this->fields as $index => $key) {
                $order[$index] = $values[$key];
            }
            $values = $order;
        }
        $this->values = array_merge($values, $this->values);
        return $this;
    }

    public function hasData()
    {
        return !!count($this->values);
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
