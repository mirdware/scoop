<?php
namespace Scoop\Persistence;

class SQO extends __SQOFactory__
{
    private $table;
    private $aliasTable;
    const READ = 1;
    const UPDATE = 2;
    const DELETE = 3;

    public function __construct($table, $alias = '', $connexion = null)
    {
        $this->table = $table;
        $this->aliasTable = $table.' '.$alias;
        $this->con = $connexion === null? \Scoop\Persistence\Driver\DBC::get(): $connexion;
    }

    public function create($fields)
    {
        ksort($fields);
        array_walk($fields, array($this, 'escape'));
        $keys = array_keys($fields);

        $query = 'INSERT INTO '.$this->table.' ('.
            implode(', ', $keys).
            ') VALUES ('.
            implode(', ', $fields).')';

        return new __SQOCreate__($query, $keys, $this->con);
    }

    public function read()
    {
        $fields = '*';
        $args = func_get_args();

        if ($args) {
            foreach ($args as $key => &$value) {
                $alias = '';
                if (is_array($value)) {                 
                    $alias = ' AS '.$value[1];
                    $value = $value[0];
                }
                if (is_object($value) && 
                    get_class($value) === '__SQOResult__' && 
                    $value->getType() === SQO::READ ) {
                    $value = '('.$value.')'.$alias;
                }
            }
            $fields = implode(', ', $args);
        }

        return new __SQOResult__('SELECT '.$fields.' FROM '.$this->aliasTable, 
                                self::READ,
                                $this->con);
    }

    public function update($fields)
    {
        array_walk($fields, array($this, 'escape'));
        $query = 'UPDATE '.$this->aliasTable.' SET ';

        foreach ($fields as $key => &$value) {
            $query .= $key.' = '.$value.', ';
        }

        return new __SQOResult__(substr($query, 0, -2), self::UPDATE, $this->con);
    }

    public function delete()
    {
        return new __SQOResult__('DELETE FROM '.$this->aliasTable, self::DELETE, $this->con);
    }

}

final class __SQOCreate__ extends __SQOFactory__
{
    private $query;
    private $keys;

    public function __construct($query, &$keys, &$connexion)
    {
        $this->query = $query;
        $this->keys =& $keys;
        $this->con =& $connexion;
    }

    public function join($fields)
    {
        ksort($fields);
        array_walk($fields, array($this, 'escape'));
        if (!array_diff($this->keys, array_keys($fields))) {
            $this->query .= ', ('.implode(', ', $fields).')';
        }
        return $this;
    }

    public function run()
    {
        echo $this->query;
        $this->con->query($this);
    }

    public function __toString()
    {
        return $this->query;
    }
}

abstract class __SQOFactory__
{
    protected $con;

    protected function escape(&$value)
    {
        if ( is_array($value) ) {
            $value = str_replace('?', $this->con->escape($value[1]), $value[0]);
        } elseif ( is_object($value) && 
            get_class($value) === '__SQOResult__' && 
            $value->getType() === SQO::READ ) {
            $value = '('.$value.') ';
        } else {
            $value = $this->con->quote($value);
        }

        return $value;
    }
}

final class __SQOResult__
{
    private $from = array();
    private $query;
    private $con;
    private $type;
    private $filter;

    public function __construct($query, $type, &$connexion)
    {
        $this->query = $query;
        $this->type = $type;
        $this->con =& $connexion;
        $this->filter = new __SQOFilter__($connexion);
    }

    public function getFilter()
    {
        return $this->filter;
    }

    public function join($table, $using = null, $type = 'INNER')
    {
        if ($type === 'LEFT' || $type === 'RIGHT' || $type === 'FULL') {
            $type .= ' OUTER';
        }
        if ($using === null) {
            $this->from[] = ', '.$table; 
        } elseif (strpos($using, '=') !== false || 
            strpos($using, '<') !== false || 
            strpos($using, '>') !== false || 
            strpos($using, '!') !== false || 
            strpos($using, ' LIKE ') !== false) {
            $this->from[] = ' '.$type.' JOIN '.$table.' ON ('.$using.')';
        } else {
            $this->from[] = ' '.$type.' JOIN '.$table.' USING ('.$using.')';
        }

        return $this;
    }

    public function run()
    {
        return $this->con->query($this);
    }

    public function getType()
    {
        return $this->type;
    }

    public function __call($name, $args)
    {
        if (method_exists ($this->filter, $name)) {
            call_user_func_array(array($this->filter, $name), $args);
            return $this;
        }
    }

    public function __toString()
    {
        return $this->query
            .implode ('', $this->from)
            .$this->filter->getRules()
            .$this->filter->getGroup()
            .$this->filter->getOrder()
            .$this->filter->getLimit();
    }

}



final class __SQOFilter__
{
    private $rules = array();
    private $order = array();
    private $group = array();
    private $orderType = ' ASC';
    private $limit = '';
    private $con;

    public function __construct(&$connexion)
    {
        $this->con =& $connexion;
    }

    public function find($rule, $replace = null)
    {
        if ($replace !== null) {
            $search = array();

            foreach ($replace as $key => &$value) {

                if (is_object($value) && 
                    get_class($value) === '__SQOResult__' && 
                    $value->getType( ) === SQO::READ) {
                    $value = '('.$value.')';
                } else {
                    $value = $this->con->quote ($value);
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
        $lastItem = func_num_args()-1;
        $desc = $args[ $lastItem ];

        if (is_bool($desc)) {
            $this->orderType = $desc? ' DESC': ' ASC';
            unset($args[$lastItem]);
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
        if ($offset === null) {
            $this->limit = '';
        } elseif ($limit === null) {
            $this->limit = ' LIMIT '.$offset;
        } else {
            $this->limit = ' LIMIT '.$limit.' OFFSET '.$offset;
        }

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