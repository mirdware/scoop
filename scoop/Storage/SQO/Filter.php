<?php
namespace Scoop\Storage\SQO;

class Filter
{
    protected $query = '';
    protected $from = array();
    protected $order = array();
    protected $group = array();
    protected $params;
    protected $sqo;
    private $rules = array();
    private $orderType = ' ASC';
    private $limit = '';
    private $having = '';
    private $connector = 'AND';
    private $type;

    public function __construct($query, $type, $sqo)
    {
        $this->params = array();
        $this->query = $query;
        $this->type = $type;
        $this->sqo = $sqo;
    }

    public function getType()
    {
        return $this->type;
    }

    public function bind($key, $value = 0)
    {
        if (is_array($key)) {
            $this->params += $key;
            return $this;
        }
        $this->params[$key] = $value;
        return $this;
    }

    public function setConnector($connector)
    {
        $this->connector = $connector;
        return $this;
    }

    public function filter($rule)
    {
        $this->rules[] = $rule;
        return $this;
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
        $this->order += $args;
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
    }

    public function limit($offset, $limit = null)
    {
        $this->limit = ' LIMIT '.($limit === null ? $offset : $limit.' OFFSET '.$offset);
        return $this;
    }

    public function run($params = null)
    {
        if (is_array($params)) {
            $this->params += $params;
        }
        $sql = $this->__toString();
        $con = $this->sqo->getConnection();
        $statement = $con->prepare($sql);
        if ($this->type !== \Scoop\Storage\SQO::READ) {
            $con->beginTransaction();
        }
        $statement->execute($this->getParamsAllowed($sql));
        return $statement;
    }

    public function __toString()
    {
        return $this->query
            .implode('', $this->from)
            .$this->getRules()
            .$this->getGroup()
            .$this->getHaving()
            .$this->getOrder()
            .$this->limit;
    }

    protected function getParamsAllowed($sql)
    {
        $allowed = array();
        preg_match_all('/:[\w_]+/', $sql, $matches);
        foreach ($matches[0] as $match) {
            $name = substr($match, 1);
            $allowed[$name] = 1;
        }
        return array_intersect_key($this->params, $allowed);
    }

    private function getRules()
    {
        $rules = $this->rules;
        foreach ($rules as $key => $rule) {
            preg_match_all('/:[\w_]+/', $rule, $matches);
            foreach ($matches[0] as $match) {
                $name = substr($match, 1);
                if (!isset($this->params[$name])) {
                    unset($rules[$key]);
                    break;
                }
                if (is_array($this->params[$name])) {
                    $rules[$key] = str_replace(':'.$name, $this->formatQueryArray($name), $rule);
                }
            }
        }
        if (empty($rules)) return '';
        return ' WHERE ('.implode(') '.$this->connector.' (', $rules).')';
    }

    private function formatQueryArray($name)
    {
        $rule = '';
        foreach ($this->params[$name] as $index => $value) {
            $this->params[$name.$index] = $value;
            $rule .= ':'.$name.$index.',';
        }
        unset($this->params[$name]);
        return substr($rule, 0, -1);
    }

    private function getOrder()
    {
        if (empty($this->order)) return '';
        return ' ORDER BY '.implode(', ', $this->order).$this->orderType;
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
