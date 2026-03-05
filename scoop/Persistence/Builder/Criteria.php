<?php

namespace Scoop\Persistence\Builder;

class Criteria extends Runner
{
    protected $from = array();
    private $filters = array();
    private $restrictions = array();
    private $connector = 'AND';
    private $query;
    private $type;

    public function __construct(\Scoop\Persistence\Connection $connection,  $query, $type, $params = array())
    {
        parent::__construct($connection, $params);
        $this->query = $query;
        $this->type = $type;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setConnector($connector)
    {
        $this->connector = $connector;
        return $this;
    }

    public function filter($rule)
    {
        $this->filters[] = $this->connection->quoteCriteria($rule);
        return $this;
    }

    public function restrict($rule)
    {
        $this->restrictions[] = $this->connection->quoteCriteria($rule);
        return $this;
    }

    public function __toString()
    {
        return $this->query
            . implode('', $this->from)
            . $this->getRules();
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

    protected function requiresTransaction()
    {
        return $this->type !== \Scoop\Persistence\SQO::READ;
    }
}
