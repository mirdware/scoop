<?php

namespace Scoop\Log;

class Logger
{
    private $handlerFactory;

    public function __construct(\Scoop\Log\Factory\Handler $handlerFactory)
    {
        $this->handlerFactory = $handlerFactory;
    }

    public function emergency($message, $context = array())
    {
        $this->log(Level::EMERGENCY, $message, $context);
    }

    public function alert($message, $context = array())
    {
        $this->log(Level::ALERT, $message, $context);
    }

    public function critical($message, $context = array())
    {
        $this->log(Level::CRITICAL, $message, $context);
    }

    public function error($message, $context = array())
    {
        $this->log(Level::ERROR, $message, $context);
    }

    public function warning($message, $context = array())
    {
        $this->log(Level::WARNING, $message, $context);
    }

    public function notice($message, $context = array())
    {
        $this->log(Level::NOTICE, $message, $context);
    }

    public function info($message, $context = array())
    {
        $this->log(Level::INFO, $message, $context);
    }

    public function debug($message, $context = array())
    {
        $this->log(Level::DEBUG, $message, $context);
    }

    public function log($level, $message, $context = array())
    {
        $handlers = $this->handlerFactory->create($level);
        foreach ($handlers as $handler) {
            $handler->handle(array(
                'message' => self::interpolate(var_export($message, true), $context),
                'context' => $context,
                'level' => $level,
                'timestamp' => new \DateTimeImmutable()
            ));
        }
    }

    protected static function interpolate($message, $context = array())
    {
        $replace = array();
        foreach ($context as $key => $value) {
            $replace['{' . $key . '}'] = !is_object($value) || method_exists($value, '__toString') ?
            $value : print_r($value, true);
        }
        return strtr($message, $replace);
    }
}
