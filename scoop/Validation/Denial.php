<?php

namespace Scoop\Validation;

class Denial extends Rule
{
    private $rule;

    public function __construct(Rule $rule)
    {
        $this->rule = $rule;
    }

    public function with($data)
    {
        return parent::with($data) && $this->rule->with($data);
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
