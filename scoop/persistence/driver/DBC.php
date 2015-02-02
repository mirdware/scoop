<?php
namespace scoop\persistence\driver;
/**
	* Clase conexion que sirve para enlazar la base de datos con
	* la aplicación y abstraer las funciones que dependen de cada
	* DBMS.
	* Autor: Marlon Ramirez
	* Version: 0.1.1
	* DBMS: PDO
**/

class DBC extends PDO {
	private static $instances = array();

	private function __construct ($db, $user, $pass, $host, $engine) {
		parent::__construct($engine.': host = '.$host.' dbname = '.$db, $user, $pass);
		parent::setAttribute(PDO::ATTR_STATEMENT_CLASS, array('result', array($this)));
		parent::setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		parent::exec('SET NAMES \'utf8\'');
	}

	private function __clone(){ }

	public static function get ($conf = NULL) {
		$bundle = 'db.default';
		if ( is_string($conf) ) {
			$bundle = $conf;
		}
		$config = \scoop\bootstrap\Config::get($bundle);
		if ( is_array($conf) ) {
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