<?php
namespace Scoop\Storage;

class DBC extends \PDO
{
    private $db;
    private $engine;
    private $host;

    public function __construct($db, $user, $pass, $host, $engine)
    {
        $this->db = $db;
        $this->engine = $engine;
        $this->host = $host;
        parent::__construct($engine.': host = '.$host.' dbname = '.$db, $user, $pass, array(
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
        ));
        \Scoop\Context::dispatchEvent(new Event\Opened($this));
    }

    public function __destruct()
    {
        if (parent::inTransaction()) {
            parent::commit();
        }
        \Scoop\Context::dispatchEvent(new Event\Closed($this));
    }

    public function beginTransaction()
    {
        if (!parent::inTransaction()) {
            return parent::beginTransaction();
        }
    }

    private function __clone() {}

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
