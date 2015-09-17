<?php
namespace Scoop\Storage;

class DBC extends \PDO
{
    private static $instances = array();
    private static $events = array(
        'pre' => array(),
        'pos' => array()
    );

    public function __construct($db, $user, $pass, $host, $engine)
    {
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

    public static function postConnect($fn)
    {
        self::$events['pos'][] = $fn;
    }

    public static function preConnect($fn)
    {
        self::$events['pre'][] = $fn;
    }

    public static function get($conf = null)
    {
        $bundle = 'db.default';
        if (is_string($conf)) {
            $bundle = $conf;
        }
        $config = \Scoop\IoC\Service::getInstance('config')->get($bundle);
        if (is_array($conf)) {
            $config += $conf;
        }
        $key = implode('', $config);

        if (!isset(self::$instances[$key])) {
            self::$instances[$key] = new DBC(
                $config['database'],
                $config['user'],
                $config['password'],
                $config['host'],
                $config['driver']
            );
        }
        return self::$instances[$key];
    }

    private static function executeEvents($type, $args = array())
    {
        foreach (self::$events[$type] as &$fn) {
            call_user_func_array($fn, $args);
        }
    }
}
