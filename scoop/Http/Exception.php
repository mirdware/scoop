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
        $config = \Scoop\Context::getService('config');
        $title = $config->get('exceptions.'.$code.'.title');
        $path =  $config->get('exceptions.'.$code.'.view');
        $this->headers = $headers;
        $this->title = $title ? $title : 'Error report';
        $this->path = $path ? 'exceptions/'.$path : 'exceptions/default';
    }

    public function addHeader($headers)
    {
        $this->headers[] = $headers;
        return $this;
    }

    public function handler()
    {
        foreach ($this->headers as $header) {
            header($header);
        }
        if (\Scoop\Context::getService('request')->isAjax()) {
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
