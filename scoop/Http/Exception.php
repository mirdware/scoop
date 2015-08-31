<?php
namespace Scoop\Http;

abstract class Exception extends \Exception
{
    private $statusCode;
    private $headers;
    private $title;

    public function __construct(
        $statusCode, 
        $message = null, 
        \Exception $previous = null, 
        array $headers = array(), 
        $code = 0)
    {
        $this->statusCode = $statusCode;
        $this->headers = $headers;
        $this->title = \Scoop\IoC\Service::getInstance('config')->get('exception.'.$statusCode);
        if (!$this->title) {
            $this->title = 'Error '.$statusCode.'!!!';
        }

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
        foreach ($this->headers as &$header) {
            header($header);
        }
        $view = new \Scoop\View('exceptions/'.$this->statusCode);
        if ($view->there()) {
            $view->set( array(
                'ex' => $this,
                'title' => $this->title
            ));
            exit ($view->render());
        }
        exit ($this->getMessage());
    }
}
