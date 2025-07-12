<?php

namespace Scoop\Persistence\SQO;

class Reader extends Filter
{
    private $order = array();
    private $group = array();
    private $orderType = ' ASC';
    private $limit = '';
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

    public function page($params)
    {
        $page = isset($params['page']) ? intval($params['page']) : 0;
        $size = isset($params['size']) ?
        intval($params['size']) :
        \Scoop\Context::inject('\Scoop\Bootstrap\Environment')->getConfig('page.size', 12);
        unset($params['page'], $params['size']);
        $paginated = new \Scoop\Persistence\SQO($this->bind($params), 'page', $this->connection);
        $result = $paginated->read()->limit($page * $size, $size)->run($params)->fetchAll();
        return $paginated->read(array('total' => 'COUNT(*)'))->run($params)
        ->fetch(\PDO::FETCH_ASSOC) + compact('page', 'size', 'result');
    }

    public function order()
    {
        $numArgs = func_num_args();
        if (!$numArgs) {
            throw new \InvalidArgumentException('Unsoported number of arguments');
        }
        $args = func_get_args();
        $type = strtoupper($args[$numArgs - 1]);
        if ($type === 'ASC' || $type === 'DESC') {
            $this->orderType = ' ' . $type;
            array_pop($args);
        }
        $this->order += array_map('trim', $args);
        return $this;
    }

    public function group()
    {
        $this->group += func_get_args();
        return $this;
    }

    public function having($condition)
    {
        $this->having = $condition;
        return $this;
    }

    public function limit($offset, $limit = null)
    {
        $this->limit = ' LIMIT ' . ($limit === null ? $offset : $limit . ' OFFSET ' . $offset);
        return $this;
    }

    public function __toString()
    {
        return parent::__toString()
            . $this->getGroup()
            . $this->getHaving()
            . $this->getOrder()
            . $this->limit;
    }

    private function getOrder()
    {
        if (empty($this->order)) {
            return '';
        }
        foreach ($this->order as $key => $element) {
            if (!preg_match('/\sASC|DESC$/i', $element)) {
                $this->order[$key] = $element . $this->orderType;
            }
        }
        return ' ORDER BY ' . implode(', ', $this->order);
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
