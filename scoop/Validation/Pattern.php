<?php
namespace Scoop\Validation;

class Pattern extends Rule
{
    protected $pattern;

    public function __construct($fields, $pattern, $mask = '')
    {
        parent::__construct($fields);
        $this->pattern = $pattern;
    }

    public function validate($value)
    {
        return preg_match('/'.$this->pattern.'/', $value);
    }
}
