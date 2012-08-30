<?php
/**
	* Clase conexion que sirve para enlazar la base de datos con
	* la aplicación y abstraer las funciones que dependen de cada
	* DBMS.
	* Autor: Marlon Ramirez
	* Version: 0.8
	* DBMS: PDO
**/

class Conexion extends PDO {
	private static $instances = array();
	
	private function __construct ($db, $user, $pass, $host, $engine) {
		parent::__construct($engine.': host = '.$host.' dbname = '.$db, $user, $pass);
		parent::setAttribute(PDO::ATTR_STATEMENT_CLASS, array('result', array($this)));
		parent::setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		parent::exec('SET NAMES \'utf8\'');
	}
	
	private function __clone(){ }
	
	public static function get ($db = '', $user = '', $pass = '', $host = '', $engine = '') {
		$key = $db.$user.$pass.$server;
		if (!isset(self::$instances[$key])) {
			self::$instances[$key] = new Conexion($db, $user, $pass, $server);
		}
		return self::$instances[$key];
	}
}

class Result extends PDOStatement {
	private $pdo;
	
	protected function BdSentencia($pdo) {
        $this->pdo = $pdo;
    }
	
	public function toAssoc() {
        return parent::fetchAll(PDO::FETCH_ASSOC);
    }
	
}
?>