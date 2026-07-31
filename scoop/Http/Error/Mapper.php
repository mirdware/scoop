<?php

namespace Scoop\Http\Error;

class Mapper
{
    const VIEW = 'exceptions/default';
    private static $messages = array();
    private static $exceptions = array(
        'Scoop\Http\Exception\Forbidden' => 403,
        'Scoop\Http\Exception\NotFound' => 404,
        'Scoop\Http\Exception\MethodNotAllowed' => 405
    );
    private $config;

    public function __construct(\Scoop\Bootstrap\Environment $environment)
    {
        $this->config = $environment->getConfig('http.errors', array());
        foreach ($this->config as $status => $config) {
            if ($status >=400 && $status <=599 && isset($config['exceptions'])) {
                foreach ($config['exceptions'] as $exception) {
                    self::$exceptions[$exception] = $status;
                }
            }
        }
    }

    public function getStatusCode($ex)
    {
        $className = get_class($ex);
        if (isset(self::$exceptions[$className])) {
            return self::$exceptions[$className];
        }
    }

    public function map($ex, $isJSON, $status)
    {
        $code = $ex->getCode();
        $headers = isset($this->config[$status]['headers']) ? $this->config[$status]['headers'] : array();
        if (isset(self::$messages[$code])) {
            $ex = new Proxy($ex, $this->interpolate($ex, self::$messages[$code]));
        }
        if ($isJSON) {
            $response = array('code' => $code, 'message' => $ex->getMessage());
            if (DEBUG_MODE) {
                $response['trace'] = $ex->__toString();
            }
            return new \Scoop\Http\Message\Response(
                $status,
                $headers + array('Content-Type' => 'application/json'),
                json_encode($response)
            );
        }
        return new \Scoop\Http\Message\Response(
            $status,
            $headers + array('Content-Type' => 'text/html'),
            $this->createView($status, $ex)->render()
        );
    }

    public static function setMessages($messages)
    {
        self::$messages = $messages;
    }

    private function createView($status, $ex)
    {
        $title = isset($this->config[$status]['title']) ? $this->config[$status]['title'] : "Error $status!";
        $view = new \Scoop\View(
            isset($this->config[$status]['view']) ? $this->config[$status]['view'] : self::VIEW
        );
        return $view->add(compact('title', 'status', 'ex'));
    }

    private function interpolate($ex, $message)
    {
        if (method_exists($ex, 'getContext')) {
            $context = $ex->getContext();
            $replace = array();
            foreach ($context as $key => $value) {
                $replace['{' . $key . '}'] = $value;
            }
            $message = strtr($message, $replace);
        }
        return $message;
    }
}
