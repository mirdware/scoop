<?php

namespace Scoop\Validation;

class Equals extends Rule
{
    protected $subject;
    private $caseSensitive;

    public function __construct($subject, $caseSensitive = false)
    {
        $this->subject = $caseSensitive ? $subject : strtolower($subject);
        $this->caseSensitive = $caseSensitive;
    }

    public function validate($value)
    {
        if (!$this->caseSensitive) {
            $value = strtolower($value);
        }
        return $value === $this->subject;
    }
}
