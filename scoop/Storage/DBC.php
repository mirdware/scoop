<?php
namespace Scoop\Storage;

class DBC extends \PDO
{
    private $db;
    private $engine;
    private $host;
    private $eventManager;

    public function __construct($db, $user, $pass, $host, $engine)
    {
        $this->eventManager = \Scoop\Context::getInjector()->getInstance('Scoop\Storage\EventManager');
        $this->db = $db;
        $this->engine = $engine;
        $this->host = $host;
        parent::__construct($engine.': host = '.$host.' dbname = '.$db, $user, $pass, array(
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
        ));
        parent::beginTransaction();
        $this->eventManager->connect($this);
    }

    public function __destruct()
    {
        parent::commit();
        $this->eventManager->disconnect($this);
    }

    private function __clone() {}

    public function getDataBase()
    {
        return $this->db;
    }

    public function getEngine()
    {
        return $this->engine;
    }

    public function getHost()
    {
        return $this->host;
    }
}
