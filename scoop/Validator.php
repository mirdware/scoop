<?php
namespace Scoop;

class Validator {
    private $data;
    private $errors;

    public function __construct($data) {
        $this->data = $data;
        $this->errors = array();
    }
    
    protected function validateRequired($field)
    {
        if (empty($this->data[$field])) {
            $this->errors[$field][] = 'Complete este campo';
        }
    }
    
    protected function validateLength($field, $max, $min)
    {
        if (isset($this->data[$field])) {
            $length = strlen($this->data[$field]);
            if ($length < $min || $length > $max) {
                $this->errors[$field][] = 'El campo no cumple con los tamaños de longitud establecidos';
            }
        }
    }
    
    protected function validateMax($field, $max)
    {
        if (isset($this->data[$field]) && is_numeric($this->data[$field])) {
            if ($this->data[$field] > $max) {
                $this->errors[$field][] = 'El campo es mayor al valor maximo establecido';
            }
        }
    }

    public function required($field)
    {
        if (is_array($field)) {
            array_walk($field, array($this, 'validateRequired'));
            return $this;
        }
        $this->validateRequired($field);
        return $this;
    }

    public function length($field, $max, $min)
    {
        if (is_array($field)) {
            foreach ($f as &$field) {
                $this->validateLength($f, $max, $min);
            }
            return $this;
        }
        $this->validateLength($field, $max, $min);
        return $this;
    }
    
    public function max($field, $max)
    {
        if (is_array($field)) {
            array_walk($field, array($this, 'validateMax'), $max);
            return $this;
        }
        $this->validateMax($field, $max);
        return $this;
    }

    public function validate()
    {
        return $this->errors;
    }
}
