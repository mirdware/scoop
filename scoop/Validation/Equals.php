<?php
namespace Scoop\Validation;

class Equals extends Rule
{
    protected $inputs;

    public function __construct($fields, $inputs) {
        parent::__construct($fields);
        $this->inputs = $inputs;
    }

    public function validate($value)
    {
        foreach ($this->inputs as $key => $input) {
            if (!empty($input) && $input !== $value) {
                $this->inputs = $key;
                return false;
            }
        }
        return true;
    }
}
