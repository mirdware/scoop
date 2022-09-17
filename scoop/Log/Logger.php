<?php
namespace Scoop\Log;

class Logger
{
    protected const DEFAULT_DATETIME_FORMAT = 'c';
    private static $handlers = array();

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
        $levelClass = '\Scoop\Log\Level::'.strtoupper($level);
        if (!defined($levelClass)) throw new \InvalidArgumentException($level.' not support level');
        if (isset(self::$handlers[$level])) {
            $handler = \Scoop\Context::inject(self::$handlers[$level]);
            $handler->handle(array(
                'message' => self::interpolate((string)$message, $context),
                'level' => $level,
                'timestamp' => (new \DateTimeImmutable())->format(self::DEFAULT_DATETIME_FORMAT)
            ));
        }
    }

    public static function setHandlers($handlers)
    {
        self::$handlers += $handlers;
    }

    protected static function interpolate($message, $context = array())
    {
        $replace = array();
        foreach ($context as $key => $value) {
            if (is_string($value) || method_exists($value, '__toString')) {
                $replace['{'.$key.'}'] = $value;
            }
        }
        return strtr($message, $replace);
    }
}
