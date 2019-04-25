<?php
namespace Scoop\Http;

class BadRequestException extends Exception
{
    public function __construct($message = 'Bad Formatted', \Exception $previous = null)
    {
        parent::__construct($message, 400, $previous, array('HTTP/1.0 400 Bad Request'));
    }

    public function handler()
    {
        $res = parent::handler();
        if (!is_array($res)) {
            \Scoop\Controller::redirect($_SERVER['HTTP_REFERER']);
        }
        return $res;
    }
}
