<?php

namespace Scoop\Validation;

class Equals extends Rule
{
    private $subject;

    public function __construct($subject)
    {
        $this->subject = $subject;
    }

    public function validate($value)
    {
        return $value === $this->subject;
    }
}
