<?php
namespace Scoop\Http;

abstract class Exception extends \Exception
{
    private $path;
    private $statusCode;
    private $headers;
    private $title;
    private $ajax;

    public function __construct(
        $statusCode,
        $message = null,
        \Exception $previous = null,
        array $headers = array(),
        $code = 0
    ) {
        parent::__construct($message, $code, $previous);
        $config = \Scoop\Context::getService('config');
        $this->statusCode = $statusCode;
        $this->headers = $headers;
        $this->title = $config->get('exception.'.$statusCode);
        $this->path = $config->get('exception.path');
        $this->ajax = \Scoop\Context::getRequest()->isAjax();
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
        $error = array(
            'title' => $this->title,
            'code' => $this->statusCode,
            'message' => $this->getMessage()
        );
        foreach ($this->headers as &$header) {
            header($header);
        }
        if ($this->ajax) {
            exit (json_encode($error));
        }
        try {
            $view = new \Scoop\View($this->path.$this->statusCode);
            exit ($view->set($error)->render());
        } catch (\Exception $ex) {
            exit ($this->getMessage());
        }
    }
}
