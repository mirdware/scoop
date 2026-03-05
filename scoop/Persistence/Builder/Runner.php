<?php

namespace Scoop\Persistence\Builder;

abstract class Runner
{
    protected $params = array();

    protected $connection;

    public function __construct(\Scoop\Persistence\Connection $connection, $params)
    {
        $this->connection = $connection;
        $this->params = $params;
    }

    public function bind($params)
    {
        if (empty($params)) {
            return $this;
        }
        $new = clone $this;
        $new->params += $params;
        return $new;
    }

    public function run($params = null)
    {
        if (is_array($params)) {
            $this->params += $params;
        }
        $sql = $this->__toString();
        $statement = $this->connection->prepare($sql);
        if ($this->requiresTransaction()) {
            $this->connection->beginTransaction();
        }
        $statement->execute($this->getParamsAllowed($sql));
        return $statement;
    }

    protected function formatQueryArray($name)
    {
        $rule = '';
        foreach ($this->params[$name] as $index => $value) {
            $this->params[$name . $index] = $value;
            $rule .= ':' . $name . $index . ',';
        }
        unset($this->params[$name]);
        return substr($rule, 0, -1);
    }

    private function getParamsAllowed($sql)
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

    abstract public function __toString();

    abstract protected function requiresTransaction();
}
