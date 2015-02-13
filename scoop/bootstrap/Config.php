<?php
namespace Scoop\Bootstrap;

abstract class Config
{
	private static $conf = array();

	public static function init()
	{
		session_start();
		/*definición global de constantes*/
		define ('ROOT', '//'.$_SERVER['HTTP_HOST'].rtrim(dirname($_SERVER['PHP_SELF']), '/\\').'/');

		/*configuración*/
		setlocale(LC_ALL, 'es_ES@euro', 'es_ES', 'esp');
		date_default_timezone_set('America/Bogota');
		set_error_handler(function ($code, $error, $file = NULL, $line = NULL) {
			throw new \Exception( $error );
		});
	}

	public static function get($name)
	{
		$name = explode('.', $name);
		$res = self::$conf;
		foreach ($name as &$key) {
			$res = $res[$key];
		}
		return $res;
	}

	public static function add($name)
	{
		self::$conf += require $name.'.php';
	}

}
