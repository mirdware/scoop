<?php
namespace Scoop\Bootstrap;

abstract class Config
{
    private static $conf = array();

    public static function get($name)
    {
        $name = explode('.', $name);
        $res = self::$conf;
        foreach ($name as &$key) {
            if (!isset($res[$key])) {
                return false;
            }
            $res = $res[$key];
        }
        return $res;
    }

    public static function add($name)
    {
        if (!self::$conf) {
            self::init();
        }
        self::$conf += require $name.'.php';
    }

    private static function init()
    {
        session_start();
        // Definición global de constantes
        define ('ROOT', '//'.$_SERVER['HTTP_HOST'].rtrim(dirname($_SERVER['PHP_SELF']), '/\\').'/');

        // Configuración
        setlocale(LC_ALL, 'es_ES@euro', 'es_ES', 'esp');
        date_default_timezone_set('America/Bogota');
        \Scoop\View\Template::addClass('View', '\Scoop\View\Helper');
    }
}
