<?php

namespace Scoop\Persistence\Builder;

class Reader extends Criteria
{
    use Pageable;
    private $group = array();
    private $having = '';

    public function __construct(\Scoop\Persistence\Connection $connection, $query)
    {
        parent::__construct($connection, $query, \Scoop\Persistence\SQO::READ);
        $this->connection = $connection;
    }

    public function join($table, $using = 'NATURAL', $type = 'INNER')
    {
        $simpleType = strtoupper($using);
        if (preg_match('/\s+as\s+/i', $table)) {
            list($table, $alias) = preg_split('/\s+as\s+/i', $table);
            $table = $this->connection->quoteColumn($table) . ' AS ' . $this->connection->quoteColumn($alias);
        } else {
            $table = $this->connection->quoteColumn($table);
        }
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
                ' ON(' . $this->connection->quoteCriteria($using) . ')' :
                ' USING(' . implode(', ', array_map(array($this->connection, 'quoteColumn'), explode(',', $using))) . ')'
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
        $condition = $this->connection->quoteCriteria($condition);
        $this->having = $this->having ? " AND ($condition)" : "($condition)";
        return $this;
    }

    public function union(Reader $reader)
    {
        return new Union($this->connection, $this, $reader, 'UNION');
    }

    public function unionAll(Reader $reader)
    {
        return new Union($this->connection, $this, $reader, 'UNION ALL');
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
        $group = array_map(array($this->connection, 'quoteColumn'), $this->group);
        return ' GROUP BY ' . implode(', ', $group);
    }
}
