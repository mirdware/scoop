<?php

namespace Scoop\Validation;

class Denial extends Rule
{
    private $rule;

    public function __construct(Rule $rule)
    {
        $this->rule = $rule;
    }

    public function with($data, $fields)
    {
        return parent::with($data, $fields) && $this->rule->with($data, $fields);
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
