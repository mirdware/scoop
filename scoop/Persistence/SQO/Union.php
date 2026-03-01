<?php

namespace Scoop\Persistence\SQO;

class Union extends Runner {
    use Pageable;
    private $base;
    private $queries = array();

    public function __construct(\Scoop\Persistence\SQO $sqo, Reader $base, Reader $other, $type)
    {
        parent::__construct($sqo, array());
        $this->base = $base;
        $this->queries[] = array('reader' => $other, 'type' => $type);
    }

    public function union(Reader $reader)
    {
        $this->queries[] = array('reader' => $reader, 'type' => 'UNION');
        return $this;
    }

    public function unionAll(Reader $reader)
    {
        $this->queries[] = array('reader' => $reader, 'type' => 'UNION ALL');
        return $this;
    }

    public function __toString()
    {
        $sql = '(' . $this->base->bind($this->params) . ')';
        foreach ($this->queries as $union) {
            $sql .= ' ' . $union['type'] . ' (' . $union['reader']->bind($this->params) . ')';
        }
        return $sql . $this->getOrder() . $this->getLimit();
    }

    protected function requiresTransaction()
    {
        return false;
    }
}
