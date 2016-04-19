<?php
namespace Scoop\Storage\SQO;

class Filter
{
    protected $from = array();
    protected $params = array();
    protected $con;
    private $rules = array();
    private $order = array();
    private $group = array();
    private $orderType = ' ASC';
    private $limit = '';
    private $query = '';
    private $connector = 'AND';
    private $type;

    public function __construct($query, $type, $params, $connexion)
    {
        $this->query = $query;
        $this->type = $type;
        $this->con = $connexion;
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
        $args = func_get_args();
        $type = strtoupper($args[func_num_args()-1]);
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
        $this->limit = ' LIMIT '.($limit === null? $offset: $limit.' OFFSET '.$offset);
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
        foreach ($rules as $key => &$rule) {
            preg_match('/:(\w+)/', $rule, $matches);
            array_shift($matches);
            foreach ($matches as &$match) {
                if (!isset($this->params[$match])) {
                    unset($rules[$key]);
                    break;
                }
            }
        }
        if (empty($rules)) {
            return '';
        }
        return ' WHERE ('.implode(') '.$this->connector.' (', $rules).')';
    }

    private function getOrder()
    {
        if (empty($this->order)) {
            return '';
        }
        return ' ORDER BY '.implode(', ', $this->order).$this->orderType;
    }

    private function getGroup()
    {
        if (empty($this->group)) {
            return '';
        }
        return ' GROUP BY '.implode(', ', $this->group);
    }
}
