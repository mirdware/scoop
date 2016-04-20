<?php
namespace Scoop\Validation;

abstract class Rule
{
    private $fields;
    private $params;
    private $name;

    public function __construct($name, $fields, $params = array())
    {
        $this->name = $name;
        $this->fields = $fields;
        $this->params = $params;
    }

    public function getFields()
    {
        return $this->fields;
    }

    public function getParams()
    {
        return $this->params;
    }

    public function getName()
    {
        return $this->name;
    }

    abstract public function validate(&$params);
}
