<?php

namespace Scoop\Validation\Rule;

class Same extends \Scoop\Validation\Rule
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
            if ($value !== $this->get($subject)) {
                $this->fail = $this->translate($subject);
                return false;
            }
        }
        return true;
    }
}
