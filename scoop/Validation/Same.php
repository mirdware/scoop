<?php

namespace Scoop\Validation;

class Same extends Rule
{
    private $env;
    private $subjects;
    private $lang;
    protected $fail;

    public function __construct()
    {
        $this->subjects = func_get_args();
    }

    public function validate($value)
    {
        foreach ($this->subjects as $subject) {
            $data = isset($this->data[$subject]) ? $this->data[$subject] : null;
            $name = isset($this->fields[$subject]) ? $this->fields[$subject] : $subject;
            if ($value !== $data) {
                $this->fail = $name;
                return false;
            }
        }
        return true;
    }
}
