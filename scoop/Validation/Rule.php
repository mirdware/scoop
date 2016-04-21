<?php
namespace Scoop\Validation;

abstract class Rule
{
    private $fields;
    private $params;
    private $name;
    private $includeInputs;

    public function __construct($name, $fields, $params = array(), $includeInputs = false)
    {
        $this->name = $name;
        $this->fields = $fields;
        $this->params = $params;
        $this->includeInputs = $includeInputs;
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

    public function isIncludeInputs()
    {
        return $this->includeInputs;
    }

    abstract public function validate(&$params);
}
