<?php

namespace Scoop\Validation;

class Same extends Rule
{
    private $subjects;
    protected $fail;

    public function __construct($subjects)
    {
        $this->subjects = $subjects;
    }

    public function validate($value)
    {
        foreach ($this->subjects as $subject => $name) {
            $subject = is_numeric($subject) ? $name : $subject;
            $data = isset($this->data[$subject]) ? $this->data[$subject] : null;
            if ($value !== $data) {
                $this->fail = $name;
                return false;
            }
        }
        return true;
    }
}
