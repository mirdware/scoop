<?php

namespace Scoop\Persistence;

class Connection
{
    private $lifecycle;
    private $name;
    private $user;
    private $password;
    private $port;
    private $engine;
    private $host;
    private $dispatcher;
    private $instance;
    private $closing = false;
    private $quoteLeft = '[';
    private $quoteRight = ']';
    private $quoteChar = '';

    public function __construct(
        \Scoop\Bootstrap\Lifecycle $lifecycle,
        \Scoop\Event\Dispatcher $dispatcher,
        $name,
        $user,
        $password,
        $host,
        $port,
        $engine
    ) {
        $this->lifecycle = $lifecycle;
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

    public function close()
    {
        if (isset($this->instance)) {
            $this->closing = true;
            unset($this->instance);
            try {
                $this->dispatcher->dispatch(new Event\ConnectionClosed($this));
            } catch (\Exception $ex) {
                error_log($ex);
            } catch (\Throwable $ex) {
                error_log($ex);
            }
            $this->closing = false;
        }
    }

    public function beginTransaction()
    {
        $instance = $this->getInstance();
        if (!$instance->inTransaction()) {
            return $instance->beginTransaction();
        }
        return false;
    }

    public function commit()
    {
        if (isset($this->instance) && $this->instance->inTransaction()) {
            return $this->instance->commit();
        }
        return false;
    }

    public function rollBack()
    {
        if (isset($this->instance) && $this->instance->inTransaction()) {
            return $this->instance->rollBack();
        }
        return false;
    }

    public function transact($callback)
    {
        if (!is_callable($callback)) {
            throw new \RuntimeException('The callback should to be callable');
        }
        $level = false;
        if (!$this->beginTransaction()) {
            $level = uniqid('LEVEL_');
            $this->exec(($this->quoteChar ? 'SAVEPOINT ' : 'SAVE TRANSACTION ') . $level);
        }
        try {
            $result = $callback($this);
            if (!$level) {
                $this->commit();
            } elseif ($this->quoteChar) {
                $this->exec("RELEASE SAVEPOINT $level");
            }
        } catch (\Exception $ex) {
            $this->rollbackTransaction($level);
            throw $ex;
        } catch (\Throwable $ex) {
            $this->rollbackTransaction($level);
            throw $ex;
        }
        return $result;
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
            return str_replace('.', $this->quoteRight . '.' . $this->quoteLeft, $name);
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

    private function rollbackTransaction($level)
    {
        try {
            if ($level) {
                $rollback = $this->quoteChar ? 'ROLLBACK TO SAVEPOINT ' : 'ROLLBACK TRANSACTION ';
                return $this->exec($rollback . $level) !== false;
            }
            return $this->rollBack();
        } catch (\Exception $ex) {
            error_log($ex);
        } catch(\Throwable $ex) {
            error_log($ex);
        }
    }

    private function getInstance()
    {
        if ($this->closing) {
            throw new \RuntimeException('Connection closed');
        }
        if (!isset($this->instance)) {
            $this->lifecycle
            ->register('complete', $this, 'commit', 2)
            ->register('rollback', $this, 'rollback')
            ->register('terminate', $this, 'close');
            $options = array(
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            );
            if ($this->is('pgsql')) {
                $options[\PDO::ATTR_EMULATE_PREPARES] = true;
            }
            $this->instance = new \PDO(
                $this->engine . ':host=' . $this->host . ';dbname=' . $this->name . ($this->port ? ';port=' . $this->port : ''),
                $this->user,
                $this->password,
                $options
            );
            $this->dispatcher->dispatch(new Event\ConnectionOpened($this));
        }
        return $this->instance;
    }
}
