<?php

namespace Scoop\Persistence\SQO;

class Filter
{
    protected $from = array();
    protected $sqo;
    private $filters = array();
    private $restrictions = array();
    private $connector = 'AND';
    private $query;
    private $params;
    private $type;

    public function __construct($query, $type, $sqo, $params = array())
    {
        $this->query = $query;
        $this->type = $type;
        $this->sqo = $sqo;
        $this->params = $params;
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
        $this->filters[] = $rule;
        return $this;
    }

    public function restrict($rule)
    {
        $this->restrictions[] = $rule;
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
        if ($this->type !== \Scoop\Persistence\SQO::READ) {
            $con->beginTransaction();
        }
        $statement->execute($this->getParamsAllowed($sql));
        return $statement;
    }

    public function __toString()
    {
        return $this->query
            . implode('', $this->from)
            . $this->getRules();
    }

    protected function getParamsAllowed($sql)
    {
        $allowed = array();
        foreach ($this->params as $name => $value) {
            if (is_array($this->params[$name])) {
                $this->formatQueryArray($name);
            }
        }
        preg_match_all('/:[\w_]+/', $sql, $matches);
        foreach ($matches[0] as $match) {
            $name = substr($match, 1);
            $allowed[$name] = 1;
        }
        return array_intersect_key($this->params, $allowed);
    }

    private function getRules()
    {
        $rules = $this->filters;
        foreach ($rules as $key => $rule) {
            preg_match_all('/:[\w_]+/', $rule, $matches);
            foreach ($matches[0] as $match) {
                $name = substr($match, 1);
                if (!isset($this->params[$name])) {
                    unset($rules[$key]);
                    break;
                }
                if (is_array($this->params[$name])) {
                    $rules[$key] = str_replace(':' . $name, $this->formatQueryArray($name), $rule);
                }
            }
        }
        $rules = array_merge($this->restrictions, $rules);
        if (empty($rules)) {
            return '';
        }
        return ' WHERE (' . implode(') ' . $this->connector . ' (', $rules) . ')';
    }

    private function formatQueryArray($name)
    {
        $rule = '';
        foreach ($this->params[$name] as $index => $value) {
            $this->params[$name . $index] = $value;
            $rule .= ':' . $name . $index . ',';
        }
        unset($this->params[$name]);
        return substr($rule, 0, -1);
    }
}
