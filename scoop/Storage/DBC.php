<?php

namespace Scoop\Storage;

class DBC extends \PDO
{
    private $db;
    private $engine;
    private $host;
    private $dispatcher;

    public function __construct($db, $user, $pass, $host, $port, $engine)
    {
        $this->db = $db;
        $this->engine = $engine;
        $this->host = $host;
        $this->dispatcher = \Scoop\Context::inject('\Scoop\Event\Dispatcher');
        parent::__construct(
            $engine . ': host = ' . $host . ' dbname = ' . $db . ($port ? ' port=' . $port : ''),
            $user,
            $pass,
            array(\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION, \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC)
        );
        $this->dispatcher->dispatch(new Event\Opened($this));
    }

    public function __destruct()
    {
        if (parent::inTransaction()) {
            parent::commit();
        }
        $this->dispatcher->dispatch(new Event\Closed($this));
    }

    public function beginTransaction()
    {
        if (!parent::inTransaction()) {
            return parent::beginTransaction();
        }
    }

    private function __clone()
    {
    }

    public function getDataBase()
    {
        return $this->db;
    }

    public function is($engine)
    {
        return $this->engine === $engine;
    }

    public function getHost()
    {
        return $this->host;
    }
}
