<?php

namespace Scoop\Log;

class Logger
{
    private $handlerFactory;
    private $handlers = array();

    public function __construct(\Scoop\Log\Factory\Handler $handlerFactory)
    {
        $this->handlerFactory = $handlerFactory;
    }

    public function emergency($message, $context = null)
    {
        $this->log(Level::EMERGENCY, $message, $context);
    }

    public function alert($message, $context = null)
    {
        $this->log(Level::ALERT, $message, $context);
    }

    public function critical($message, $context = null)
    {
        $this->log(Level::CRITICAL, $message, $context);
    }

    public function error($message, $context = null)
    {
        $this->log(Level::ERROR, $message, $context);
    }

    public function warning($message, $context = null)
    {
        $this->log(Level::WARNING, $message, $context);
    }

    public function notice($message, $context = null)
    {
        $this->log(Level::NOTICE, $message, $context);
    }

    public function info($message, $context = null)
    {
        $this->log(Level::INFO, $message, $context);
    }

    public function debug($message, $context = null)
    {
        $this->log(Level::DEBUG, $message, $context);
    }

    public function log($level, $message, $context = null)
    {
        if (!isset($this->handlers[$level])) {
            $this->handlers[$level] = $handler = $this->handlerFactory->create($level);
        }
        $handlers = $this->handlers[$level];
        if (empty($handlers)) {
            return;
        }
        $record = array(
            'message' => self::interpolate($message, $context ? $context : array()),
            'context' => $context,
            'level' => $level,
            'timestamp' => new \DateTime(),
        );
        foreach ($handlers as $handler) {
            try {
                $handler->handle($record);
            }  catch (\Exception $ex) {
                error_log($ex);
            } catch (\Throwable $ex) {
                error_log($ex);
            }
        }
    }

    public function flush()
    {
        foreach ($this->handlers as $handlers) {
            foreach ($handlers as $handler) {
                if (is_callable(array($handler, 'flush'))) {
                    $handler->flush();
                }
            }
        }
    }

    protected static function interpolate($message, $context = array())
    {
        if (!is_scalar($message) && !method_exists($message, '__toString')) {
            $message = print_r($message, true);
        }
        $replace = array();
        foreach ($context as $key => $value) {
            $placeholder = '{' . $key . '}';
            if (strpos($message, $placeholder) !== false) {
                try {
                    $replace[$placeholder] = is_scalar($value) || $value === null ||
                    (is_object($value) && method_exists($value, '__toString')) ?
                    (string) $value :
                    print_r($value, true);
                } catch (\Exception $error) {
                    $replace[$placeholder] = '[Exception: ' . get_class($error) . ']';
                } catch (\Throwable $error) {
                    $replace[$placeholder] = '[Throwable: ' . get_class($error) . ']';
                }
            }
        }
        return strtr($message, $replace);
    }
}
