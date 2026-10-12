<?php

namespace Scoop\Validation\Rule;

class In extends \Scoop\Validation\Rule
{
    private $array;
    protected $allowed;

    public function __construct($allowed)
    {
        $this->array = $allowed;
        $this->allowed = implode(', ', $allowed);
    }

    public function validate($value)
    {
        return in_array($value, $this->array, true);
    }
}
