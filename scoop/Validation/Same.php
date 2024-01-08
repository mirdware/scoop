<?php

namespace Scoop\Validation;

class Same extends Rule
{
    private $subjects;
    protected $fail;

    public function __construct()
    {
        $this->subjects = func_get_args();
    }

    public function validate($value)
    {
        foreach ($this->subjects as $subject) {
            if ($value !== $this->data[$subject]) {
                $this->fail = $subject;
                return false;
            }
        }
        return true;
    }
}
