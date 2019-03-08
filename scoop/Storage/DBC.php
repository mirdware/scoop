<?php
namespace Scoop\Storage;

class DBC extends \PDO
{
    private $db;
    private $engine;
    private $host;
    private static $events = array(
        'pre' => array(),
        'pos' => array()
    );

    public function __construct($db, $user, $pass, $host, $engine)
    {
        $this->db = $db;
        $this->engine = $engine;
        $this->host = $host;
        self::executeEvents('pre');
        parent::__construct($engine.': host = '.$host.' dbname = '.$db, $user, $pass, array(
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
        ));
        parent::exec('SET NAMES \'utf8\'');
        parent::beginTransaction();
        self::executeEvents('pos', array($this));
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

    public static function postConnect($fn)
    {
        self::$events['pos'][] = $fn;
    }

    public static function preConnect($fn)
    {
        self::$events['pre'][] = $fn;
    }

    private static function executeEvents($type, $args = array())
    {
        foreach (self::$events[$type] as &$fn) {
            call_user_func_array($fn, $args);
        }
    }
}
