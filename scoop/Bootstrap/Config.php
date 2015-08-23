<?php
namespace Scoop\Bootstrap;

class Config
{
    private $conf = array();
    private static $init = false;

    public function __construct()
    {
        if (!self::$init) {
            self::init();
        }
    }

    public function get($name)
    {
        $name = explode('.', $name);
        $res = $this->conf;
        foreach ($name as &$key) {
            if (!isset($res[$key])) {
                return false;
            }
            $res = $res[$key];
        }
        return $res;
    }

    public function add($name)
    {
        $this->conf += require $name.'.php';
    }

    private static function init()
    {
        session_start();
        // Definición global de constantes
        define ('ROOT', '//'.$_SERVER['HTTP_HOST'].rtrim(dirname($_SERVER['PHP_SELF']), '/\\').'/');

        // Configuración
        setlocale(LC_ALL, 'es_ES@euro', 'es_ES', 'esp');
        date_default_timezone_set('America/Bogota');
        self::$init = true;
    }
}
