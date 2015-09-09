<?php
namespace Scoop\Storage\SQO;

final class Filter
{
    private $rules = array();
    private $order = array();
    private $group = array();
    private $orderType = ' ASC';
    private $limit = '';
    private $con;

    public function __construct(&$connexion)
    {
        $this->con = &$connexion;
    }

    public function find($rule, $replace = null)
    {
        if ($replace !== null) {
            $search = array();
            foreach ($replace as $key => &$value) {
                if (is_object($value) &&
                    $value instanceof Result &&
                    $value->getType() === \Scoop\Storage\SQO::READ) {
                    $value = '('.$value.')';
                } else {
                    $value = $this->con->quote($value);
                }
                $search[] = ':'.$key;
            }
            $rule = str_replace($search, $replace, $rule);
        }
        $this->rules[] = '('.$rule.')';
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

    public function getRules()
    {
        if (empty($this->rules)) {
            return '';
        }
        return ' WHERE '.implode(' AND ', $this->rules);
    }

    public function getOrder()
    {
        if (empty($this->order)) {
            return '';
        }
        return ' ORDER BY '.implode(', ', $this->order).$this->orderType;
    }

    public function getGroup()
    {
        if (empty($this->group)) {
            return '';
        }
        return ' GROUP BY '.implode(', ', $this->group);
    }

    public function getLimit()
    {
        return $this->limit;
    }
}
