<?php
namespace Scoop\Http;

abstract class Exception extends \Exception
{
    private $statusCode;
    private $headers;

    public function __construct(
        $statusCode, 
        $message = null, 
        \Exception $previous = null, 
        array $headers = array(), 
        $code = 0)
    {
        $this->statusCode = $statusCode;
        $this->headers = $headers;

        parent::__construct($message, $code, $previous);
    }

    public function getStatusCode()
    {
        return $this->statusCode;
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    public function handler()
    {
        $view = new \Scoop\View('exceptions/'.$this->statusCode);
        $view->set( array(
            'ex' => $this,
            'title' => 'Error '.$this->statusCode.'!!!'
        ));
        foreach ($this->headers as &$header) {
            header($header);
        }
        exit($view->render());
    }
}