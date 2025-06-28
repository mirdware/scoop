<?php

namespace Scoop\Http\Exception;

class Manager
{
    const VIEW = 'exceptions/default';
    private static $messages = array();
    private static $exceptions = array(
        'Scoop\Http\Exception\NotFound' => 404,
        'Scoop\Http\Exception\MethodNotAllowed' => 405
    );
    private static $errors = array(
        400 => 'Bad Request',
        401 => 'Not Authorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Time-out',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Large',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Time-out',
        505 => 'HTTP Version Not Supported'
    );
    private $config;

    public function __construct(\Scoop\Bootstrap\Environment $environment)
    {
        $this->config = $environment->getConfig('http.errors', array());
        foreach ($this->config as $status => $config) {
            if (isset(self::$errors[$status]) && isset($config['exceptions'])) {
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

    public function handle($ex, $isJSON, $status)
    {
        $code = $ex->getCode();
        $this->addHeaders($status);
        if (isset(self::$messages[$code])) {
            $ex = new \Scoop\Http\Exception\Proxy($ex, self::$messages[$code]);
        }
        if ($isJSON) {
            header('Content-Type: application/json');
            return array('code' => $code, 'message' => $ex->getMessage());
        }
        return $this->createView($status, $ex);
    }

    public static function setMessages($messages)
    {
        self::$messages = $messages;
    }

    private function addHeaders($status)
    {
        $headers = isset($this->config[$status]['headers']) ? $this->config[$status]['headers'] : array();
        foreach ($headers as $header) {
            header($header);
        }
        header('HTTP/1.1 ' . $status . ' ' . self::$errors[$status]);
    }

    private function createView($status, $ex)
    {
        $title = isset($this->config[$status]['title']) ? $this->config[$status]['title'] : self::$errors[$status];
        $view = isset($this->config[$status]['view']) ? $this->config[$status]['view'] : self::VIEW;
        $view = new \Scoop\View($view);
        return $view->add(compact('title', 'status', 'ex'));
    }
}
