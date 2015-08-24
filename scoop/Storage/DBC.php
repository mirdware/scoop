<?php
namespace Scoop\Storage;

class DBC extends \PDO
{
    private static $instances = array();

    public function __construct($db, $user, $pass, $host, $engine)
    {
        parent::__construct($engine.': host = '.$host.' dbname = '.$db, $user, $pass);
        parent::setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
        parent::setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        parent::exec('SET NAMES \'utf8\'');
        parent::beginTransaction();
    }

    public function __destruct()
    {
        parent::commit();
    }

    private function __clone() {}

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
}
