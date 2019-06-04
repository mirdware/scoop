<?php
namespace Scoop\Storage\SQO;

class Filter
{
    protected $from = array();
    protected $params = array();
    protected $query = '';
    protected $con;
    private $rules = array();
    private $order = array();
    private $group = array();
    private $orderType = ' ASC';
    private $limit = '';
    private $connector = 'AND';
    private $type;

    public function __construct($query, $type, $params, $connection)
    {
        $this->query = $query;
        $this->type = $type;
        $this->con = $connection;
        $this->params = $params;
    }

    public function getType()
    {
        return $this->type;
    }

    public function bindParam($key, $value)
    {
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

    public function limit($offset, $limit = null)
    {
        $this->limit = ' LIMIT '.($limit === null ? $offset : $limit.' OFFSET '.$offset);
        return $this;
    }

    public function run($params = null)
    {
        if ($params !== null) {
            $this->params += $params;
        }
        $statement = $this->con->prepare($this);
        $statement->execute($this->params);
        return $statement;
    }

    public function __toString()
    {
        return $this->query
            .implode('', $this->from)
            .$this->getRules()
            .$this->getGroup()
            .$this->getOrder()
            .$this->limit;
    }

    private function getRules()
    {
        $rules = $this->rules;
        foreach ($rules as $key => $rule) {
            preg_match_all('/:[\w_]+/', $rule, $matches);
            foreach ($matches[0] as $match) {
                if (!isset($this->params[substr($match, 1)])) {
                    unset($rules[$key]);
                    break;
                }
            }
        }
        if (empty($rules)) return '';
        return ' WHERE ('.implode(') '.$this->connector.' (', $rules).')';
    }

    private function getOrder()
    {
        if (empty($this->order)) return '';
        return ' ORDER BY '.implode(', ', $this->order).$this->orderType;
    }

    private function getGroup()
    {
        if (empty($this->group)) return '';
        return ' GROUP BY '.implode(', ', $this->group);
    }
}
