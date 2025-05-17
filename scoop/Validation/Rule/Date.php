<?php

namespace Scoop\Validation\Rule;

class Date extends \Scoop\Validation\Rule
{
    protected $format;

    public function __construct($format = '')
    {
        $this->format = $format;
    }

    public function validate($value)
    {
        if ($this->format) {
            $dateTime = \DateTime::createFromFormat($this->format, $value);
            return $dateTime && $dateTime->format($this->format) === $value;
        }
        return strtotime($value) !== false;
    }
}
