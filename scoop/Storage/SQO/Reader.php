<?php
namespace Scoop\Storage\SQO;

class Reader extends Filter
{
    private $order = array();
    private $group = array();
    private $orderType = ' ASC';
    private $limit = '';
    private $having = '';

    public function __construct($query, $sqo)
    {
        parent::__construct($query, \Scoop\Storage\SQO::READ, $sqo);
    }

    public function join($table, $using = 'NATURAL', $type = 'INNER')
    {
        $simpleType = strtoupper($using);
        if ($simpleType === 'CROSS' || $simpleType === 'NATURAL') {
            $this->from[] = ' '.$simpleType.' JOIN '.$table;
            return $this;
        }
        $type = strtoupper($type);
        if ($type !== 'INNER') {
            $type .= ' OUTER';
        }
        $this->from[] = ' '.$type.' JOIN '.$table.(
            preg_match('/\s*([<>!=]{1,2}|NOT ?LIKE)\s*/', $using) ?
                ' ON('.$using.')' :
                ' USING('.$using.')'
        );
        return $this;
    }

    public function page($params)
    {
        $page = isset($params['page']) ? intval($params['page']) : 0;
        $size = isset($params['size']) ?
        intval($params['size']) :
        \Scoop\Context::getEnvironment()->getConfig('page.size', 12);
        unset($params['page'], $params['size']);
        $sql = 'SELECT COUNT(*) AS total FROM ('.$this->bind($params).') d';
        $paginated = $this->sqo->getConnection()->prepare($sql);
        $paginated->execute($this->getParamsAllowed($sql));
        $clone = clone $this;
        $result = $clone->limit($page * $size, $size)->run()->fetchAll();
        return $paginated->fetch(\PDO::FETCH_ASSOC) + compact('page', 'size', 'result');
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
            $this->orderType = ' '.$type;
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
        $this->limit = ' LIMIT '.($limit === null ? $offset : $limit.' OFFSET '.$offset);
        return $this;
    }

    public function __toString()
    {
        return parent::__toString()
            .$this->getGroup()
            .$this->getHaving()
            .$this->getOrder()
            .$this->limit;
    }

    private function getOrder()
    {
        if (empty($this->order)) return '';
        $order = ' ORDER BY ';
        foreach ($this->order as $element) {
            if (!preg_match('/\sASC|DESC$/i', $element)) {
                $element .= $this->orderType;
            }
            $order = $element.', ';
        }
        return substr($order, -2);
    }

    private function getHaving()
    {
        if (!$this->having) return '';
        return ' HAVING '.$this->having;
    }

    private function getGroup()
    {
        if (empty($this->group)) return '';
        return ' GROUP BY '.implode(', ', $this->group);
    }
}
