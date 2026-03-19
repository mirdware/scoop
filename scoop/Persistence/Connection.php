<?php

namespace Scoop\Persistence;

class Connection
{
    private $name;
    private $user;
    private $password;
    private $port;
    private $engine;
    private $host;
    private $dispatcher;
    private $instance;
    private $quoteLeft = '[';
    private $quoteRight = ']';
    private $quoteChar = '';

    public function __construct($dispatcher, $name, $user, $password, $host, $port, $engine)
    {
        $this->name = $name;
        $this->user = $user;
        $this->password = $password;
        $this->port = $port;
        $this->engine = strtolower($engine);
        $this->host = $host;
        $this->dispatcher = $dispatcher;
        if (!$this->is('sqlsrv') && !$this->is('dblib')) {
            $this->quoteChar = $this->is('mysql') ? '`' : '"';
            $this->quoteRight = $this->quoteLeft = $this->quoteChar;
        }
    }

    public static function nullify($parameters)
    {
        $result = array();
        foreach ($parameters as $key => $value) {
            $result[$key] = $value === '' ? null : $value;
        }
        return $result;
    }

    public function __destruct()
    {
        if ($this->instance) {
            $this->commit();
            $this->dispatcher->dispatch(new Event\ConnectionClosed($this));
        }
    }

    public function beginTransaction()
    {
        $instance = $this->getInstance();
        if (!$instance->inTransaction()) {
            return $instance->beginTransaction();
        }
    }

    public function commit()
    {
        if ($this->instance && $this->instance->inTransaction()) {
            $this->instance->commit();
        }
    }

    public function rollBack()
    {
        if ($this->instance && $this->instance->inTransaction()) {
            $this->instance->rollBack();
        }
    }

    public function errorCode()
    {
        return $this->getInstance()->errorCode();
    }

    public function errorInfo()
    {
        return $this->getInstance()->errorInfo();
    }

    public function prepare($statement, $driver_options = array())
    {
        return $this->getInstance()->prepare($statement, $driver_options);
    }

    public function query($statement)
    {
        return $this->getInstance()->query($statement);
    }

    public function exec($statement)
    {
        return $this->getInstance()->exec($statement);
    }

    public function getAttribute($attribute)
    {
        return $this->getInstance()->getAttribute($attribute);
    }

    public function lastInsertId($name = null)
    {
        return $this->getInstance()->lastInsertId($name);
    }

    public function quote($string, $parameterType = null)
    {
        return $this->getInstance()->quote($string, $parameterType);
    }

    public function setAttribute($attribute, $value)
    {
        return $this->getInstance()->setAttribute($attribute, $value);
    }

    public function inTransaction()
    {
        return $this->getInstance()->inTransaction();
    }

    public function getDataBase()
    {
        return $this->name;
    }

    public function is($engine)
    {
        return $this->engine === strtolower($engine);
    }

    public function getHost()
    {
        return $this->host;
    }

    public function quoteColumn($name, $silence = false)
    {
        $name = trim($name);
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_\.\$]*$/', $name)) {
            if ($silence) return $name;
            throw new \DomainException("Invalid SQL identifier detected: $name");
        }
        $name = $this->quoteLeft . $name . $this->quoteRight;
        if (strpos($name, '.') !== false) {
            return str_replace('.', $this->quoteLeft . '.' . $this->quoteRight, $name);
        }
        return $name;
    }

    public function quoteCriteria($string)
    {
        if (!$this->quoteChar) {
            return $string;
        }
        return str_replace(array('[', ']'), $this->quoteChar, $string);
    }

    private function __clone()
    {
    }

    private function getInstance()
    {
        if (!$this->instance) {
            $this->instance = new \PDO(
                $this->engine . ':host=' . $this->host . ';dbname=' . $this->name . ($this->port ? ';port=' . $this->port : ''),
                $this->user,
                $this->password,
                array(\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION, \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC)
            );
            $this->dispatcher->dispatch(new Event\ConnectionOpened($this));
        }
        return $this->instance;
    }
}
