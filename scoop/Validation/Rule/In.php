<?php

namespace Scoop\Validation\Rule;

class In extends \Scoop\Validation\Rule
{
    protected $allowed;

    public function __construct($allowed)
    {
        $this->allowed = $allowed;
    }

    public function validate($value)
    {
        return in_array($value, $this->allowed, true);
    }
}
