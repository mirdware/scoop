<?php
namespace Scoop\Http;

abstract class Exception extends \Exception
{
    private $headers;
    private $path;
    private $title;

    public function __construct($message, $code, $previous, $headers)
    {
        parent::__construct($message, $code, $previous);
        $environment = \Scoop\Context::getEnvironment();
        $title = $environment->getConfig('exceptions.'.$code.'.title');
        $path =  $environment->getConfig('exceptions.'.$code.'.view');
        $this->headers = $headers;
        $this->title = $title ? $title : 'Error report';
        $this->path = $path ? 'exceptions/'.$path : 'exceptions/default';
    }

    public function addHeader($header)
    {
        $this->headers[] = $header;
        return $this;
    }

    public function handler()
    {
        foreach ($this->headers as $header) {
            header($header);
        }
        if (in_array('Content-Type: application/json', $this->headers)) {
            return array(
                'code' => $this->getCode(),
                'message' => $this->getMessage()
            );
        }
        $view = new \Scoop\View($this->path);
        return $view->set(
            array('title' => $this->title, 'ex' => $this)
        );
    }
}
