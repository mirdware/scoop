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
        $code = 0
    ) {
        parent::__construct($message, $code, $previous);
        $config = \Scoop\Context::getService('config');
        $title = $config->get('exception.'.$statusCode);
        $path =  $config->get('exception.path');
        $this->statusCode = $statusCode;
        $this->headers = $headers;
        $this->title = $title ? $title : 'Error '.$statusCode.'!!!';
        $this->path = ($path ? $path : 'exceptions/').$statusCode;
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
        foreach ($this->headers as $header) {
            header($header);
        }
        if (\Scoop\Context::getRequest()->isAjax()) {
            exit (json_encode($error));
        }
        try {
            $view = new \Scoop\View($this->path);
            exit ($view->set($error)->render());
        } catch (\Exception $ex) {
            exit ($this->getMessage());
        }
    }
}
