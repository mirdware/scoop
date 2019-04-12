<?php
namespace Scoop\Validation;

class Equals extends Rule
{
    protected $inputs;
    protected $failInput;

    public function __construct($fields, $inputs)
    {
        parent::__construct($fields);
        $this->inputs = $inputs;
    }

    public function validate($value)
    {
        if (empty($value)) return true;
        foreach ($this->values as $key => $input) {
            if ($input !== $value) {
                $this->failInput = $key;
                return false;
            }
        }
        return true;
    }
}
