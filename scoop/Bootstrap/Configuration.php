<?php
namespace Scoop\Bootstrap;

class Configuration
{
    private $config = array();
    private static $init = false;

    public function __construct($fileName)
    {
        if (!self::$init) {
            self::init();
        }
        $this->add($fileName);
    }

    public function get($name)
    {
        $name = explode('.', $name);
        $res = $this->config;
        foreach ($name as &$key) {
            if (!isset($res[$key])) {
                return false;
            }
            $res = $res[$key];
        }
        return $res;
    }

    public function add($fileName)
    {
        $this->config += require $fileName.'.php';
    }

    private static function init()
    {
        session_start();
        setlocale(LC_ALL, 'es_ES@euro', 'es_ES', 'esp');
        date_default_timezone_set('America/Bogota');
        self::$init = true;
    }
}
