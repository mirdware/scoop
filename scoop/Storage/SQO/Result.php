<?php
namespace Scoop\Storage\SQO;

final class Result
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
        $this->con = &$connexion;
        $this->filter = new Filter($connexion);
    }

    public function getFilter()
    {
        return $this->filter;
    }

    public function getConnexion()
    {
        return $this->con;
    }

    public function join($table, $using = null, $type = 'INNER')
    {
        $join = ', '.$table;
        if ($type === 'LEFT' || $type === 'RIGHT' || $type === 'FULL') {
            $type .= ' OUTER';
        }

        if ($using !== null) {
            $join = ' '.$type.' JOIN '.$table
                .((strpos($using, '=') !== false ||
                   strpos($using, '<') !== false ||
                   strpos($using, '>') !== false ||
                   strpos($using, '!') !== false ||
                   strpos($using, ' LIKE ') !== false)?
                        ' ON('.$using.')':
                        ' USING('.$using.')');
        }
        $this->from[] = $join;
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
        call_user_func_array(array($this->filter, $name), $args);
        return $this;
    }

    public function __clone()
    {
        $this->filter = clone $this->filter;
    }

    public function __toString()
    {
        return $this->query
            .implode('', $this->from)
            .$this->filter->getRules()
            .$this->filter->getGroup()
            .$this->filter->getOrder()
            .$this->filter->getLimit();
    }
}
