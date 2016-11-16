<?php
namespace Scoop\Http;

abstract class Exception extends \Exception
{
    private $path;
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
        $config = \Scoop\IoC\Service::getInstance('config');
        parent::__construct($message, $code, $previous);
        $this->statusCode = $statusCode;
        $this->headers = $headers;
        $this->title = $config->get('exception.'.$statusCode);
        $this->path = $config->get('exception.path');
        if (!$this->title) {
            $this->title = 'Error '.$statusCode.'!!!';
        }
        if (!$this->path) {
            $this->path = 'exceptions/';
        }
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
        try {
            $view = new \Scoop\View($this->path.$this->statusCode);
            $output = $view->set(array(
                'ex' => $this,
                'title' => $this->title
            ))->render();
        } catch (\Exception $ex) {
            $output = $this->getMessage();
        }
        exit($output);
    }
}
