<?php
namespace Scoop\Storage\SQO;

class Result
{
    protected $from = array();
    protected $con;
    private $query;
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

    public function getType()
    {
        return $this->type;
    }

    public function run()
    {
        return $this->con->query($this);
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
