<?php
namespace Scoop\Http;

abstract class Exception extends \Exception
{
    private $path;
    private $headers;
    private $title;

    public function __construct($message, $code, $previous, $headers)
    {
        parent::__construct($message, $code, $previous);
        $config = \Scoop\Context::getService('config');
        $title = $config->get('exception.'.$code.'.title');
        $path =  $config->get('exception.'.$code.'.view');
        $this->headers = $headers;
        $this->title = $title ? $title : 'Error report';
        $this->path = $path ? 'exceptions/'.$path : 'exceptions/default';
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    public function handler()
    {
        foreach ($this->headers as $header) {
            header($header);
        }
        if (\Scoop\Context::getService('request')->isAjax()) {
            exit ($this);
        }
        try {
            $view = new \Scoop\View($this->path);
            exit ($view->set(
                array('title' => $this->title, 'ex' => $this)
            )->render());
        } catch (\UnderflowException $ex) {
            exit ($this);
        }
    }

    public function __toString()
    {
        return json_encode(array(
            'code' => $this->getCode(),
            'message' => $this->getMessage()
        ));
    }
}
