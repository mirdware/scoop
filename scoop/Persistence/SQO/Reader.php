<?php

namespace Scoop\Persistence\SQO;

class Reader extends Criteria
{
    use Pageable;
    private $group = array();
    private $having = '';
    private $connection;

    public function __construct($query, $sqo, $connection)
    {
        parent::__construct($query, \Scoop\Persistence\SQO::READ, $sqo);
        $this->connection = $connection;
    }

    public function join($table, $using = 'NATURAL', $type = 'INNER')
    {
        $simpleType = strtoupper($using);
        if ($simpleType === 'CROSS' || $simpleType === 'NATURAL') {
            $this->from[] = ' ' . $simpleType . ' JOIN ' . $table;
            return $this;
        }
        $type = strtoupper($type);
        if ($type !== 'INNER') {
            $type .= ' OUTER';
        }
        $this->from[] = ' ' . $type . ' JOIN ' . $table . (
            preg_match('/\s*([<>!=]{1,2}|NOT ?LIKE)\s*/', $using) ?
                ' ON(' . $using . ')' :
                ' USING(' . $using . ')'
        );
        return $this;
    }

    public function group()
    {
        $this->group += func_get_args();
        return $this;
    }

    public function having($condition)
    {
        $this->having = $this->having ? " AND ($condition)" : "($condition)";
        return $this;
    }

    public function union(Reader $reader)
    {
        return new Union($this->sqo, $this, $reader, 'UNION');
    }

    public function unionAll(Reader $reader)
    {
        return new Union($this->sqo, $this, $reader, 'UNION ALL');
    }

    public function __toString()
    {
        return parent::__toString()
            . $this->getGroup()
            . $this->getHaving()
            . $this->getOrder()
            . $this->getLimit();
    }

    private function getHaving()
    {
        if (empty($this->having)) {
            return '';
        }
        return ' HAVING ' . $this->having;
    }

    private function getGroup()
    {
        if (empty($this->group)) {
            return '';
        }
        return ' GROUP BY ' . implode(', ', $this->group);
    }
}
