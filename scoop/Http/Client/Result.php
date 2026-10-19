<?php

namespace Scoop\Http\Client;

class Result
{
    private $responses;
    private $errors;

    public function __construct(array $responses = array(), array $errors = array())
    {
        $this->responses = $responses;
        $this->errors = $errors;
    }

    public function getResponses()
    {
        return $this->responses;
    }

    public function getErrors()
    {
        return $this->errors;
    }

    public function hasErrors()
    {
        return count($this->errors) > 0;
    }
}
