<?php
namespace Scoop\Storage;

class DBC extends \PDO
{
    private $db;
    private $engine;
    private $host;

    public function __construct($db, $user, $pass, $host, $engine)
    {
        $eventManager = \Scoop\Context::getInjector()->getInstance('Scoop\Storage\EventManager');
        $this->db = $db;
        $this->engine = $engine;
        $this->host = $host;
        $eventManager->preConnect();
        parent::__construct($engine.': host = '.$host.' dbname = '.$db, $user, $pass, array(
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
        ));
        parent::beginTransaction();
        $eventManager->posConnect($this);
    }

    public function __destruct()
    {
        parent::commit();
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
