<?php

namespace Scoop\Validation\Rule;

class Denial extends \Scoop\Validation\Rule
{
    private $rule;

    public function __construct(\Scoop\Validation\Rule $rule)
    {
        $this->rule = $rule;
    }

    public function attach($data, $fields)
    {
        return parent::attach($data, $fields) && $this->rule->attach($data, $fields);
    }

    public function unwrap()
    {
        return $this->rule;
    }

    public function getParams()
    {
        return $this->rule->getParams();
    }

    public function validate($value)
    {
        return !$this->rule->validate($value);
    }
}
