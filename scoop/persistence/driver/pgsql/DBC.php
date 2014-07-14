<?php
namespace scoop\persistence\driver\pgsql;
/**
	* Clase conexion que sirve para enlazar la base de datos con
	* la aplicación y abstraer las funciones que dependen de cada
	* DBMS.
	* Autor: Marlon Ramirez
	* Version: 0.8
	* DBMS: postgreSQL
**/

class DBC {
	//conexion persistente a la base de datos
	private $conex;
	//Lista de conexiones existentes en la ejecución de la aplicación
	private static $instances = array();
	const FETCH_ASSOC = 1;
	const FETCH_BOTH = 2;
	const FETCH_NUM = 3;
	const FETCH_OBJ = 4;
	
	/*constructor*/
	private function __construct($db, $user, $pass, $host) {
		$this->conex = pg_connect(
			'host='.$host.' port=5432 dbname='.$db.
			' user='.$user.' password='.$pass
		) or die();
		$this->query('SET NAMES \'utf8\'');
	}
	
	public function __destruct(){
		if ($this->conex) {
			pg_close($this->conex);
		}
	}

	/*Inpedir el clonado de objetos*/
	private function __clone(){ }
	
	/*Patron Multiton*/
	public static function get ($config = array()) {
		$config || ($config = \scoop\bootstrap\Config::get('db.pgsql'));
		$key = implode('', $config);
		if (!isset(self::$instances[$key])) {
			self::$instances[$key] = new Conexion(
				$config['database'], 
				$config['user'], 
				$config['password'], 
				$config['host']
			);
		}
		return self::$instances[$key];
	}
	
	/*abstraccion de los metodos independiente del DBMS*/
	public function query($consulta) {
		if(!$this->conex) {
			return FALSE;
		}
		
		$consulta = trim($consulta);
		//echo $consulta;
		$r = pg_query($this->conex, $consulta);
		if ( !$r ) {
			throw new SQLException($this->error(), 1);
		}
		
		if(strpos(strtoupper($consulta), 'SELECT') === 0) {
			$res = new __Result__($r);
			return $res;
		} else {
			return $r;
		}
	}
	
	public function error(){
		return pg_last_error($this->conex);
	}
	
	public function escape($val) {
		$val = trim($val);
		if ($val === NULL || $val === '') {
			return 'NULL';
		}
		if (get_magic_quotes_gpc()) {
        	$val = stripslashes($val);
		}
		$val = "'" . pg_escape_string($val) . "'";
		return $val;
	}
	
	public function lastId() {
		return $this->query('SELECT lastval()')->result(0);
	}
	
}

//**********************************************************************************

final class __Result__ {
	private $res;
	
	public function __construct($res) {
		$this->res = $res;
	}
	
	public function __destruct() {
		if($this->res){
			pg_free_result($this->res);
		}
	}
	
	/*abstraccion de los metodos independiente del DBMS*/
	public function numRows() {
		return pg_num_rows($this->res);
	}
	
	public function toObject() {
		return pg_fetch_object($this->res);
	}
	
	public function toArray() {
		return pg_fetch_array($this->res);
	}
	
	public function toAssoc() {
		return pg_fetch_assoc($this->res);
	}

	public function toRow () {
		return pg_fetch_row($this->res);
	}
	
	public function result($row=0, $field=0) {
		return pg_fetch_result($this->res,$row, $field);
	}
	
	public function reset() {
		pg_result_seek($this->res, 0);
	}
}

class SQLException extends Exception {
	/** Information that provides additional information for context of Exception (e.g. SQL statement or DSN). */
	protected $userInfo;

	/** Native RDBMS error string */
	protected $nativeError;

	/**
		* Constructs a SQLException.
		* @param string $msg Error message
		* @param string $native Native DB error message.
		* @param string $userinfo More info, e.g. the SQL statement or the connection string that caused the error.
	*/
	public function __construct($msg, $native = null, $userinfo = null) {
		parent::__construct($msg);
		if ($native !== null) {
			$this->setNativeError($native);
		}
		if ($userinfo !== null) {
			$this->setUserInfo($userinfo);
		}
	}

	/**
		* Sets additional user / debug information for this error.
		* 
		* @param array $info
		* @return void
	*/
	public function setUserInfo($info) {
		$this->userInfo = $info;
		$this->message .= " [User Info: " .$this->userInfo . "]";
	}


	/**
		* Returns the additional / debug information for this error.
		*
		* @return array hash of user info properties.
	*/
	public function getUserInfo() {
		return $this->userInfo;
	}

	/**
		* Sets driver native error message.
		* 
		* @param string $info
		* @return void
		*/
	public function setNativeError($msg) {
		$this->nativeError = $msg;
		$this->message .= " [Native Error: " .$this->nativeError . "]";
	}

	/**
		* Gets driver native error message.
		*
		* @return string
	*/
	public function getNativeError() {
		return $this->nativeError;
	}       

	/**
		* @deprecated This method only exists right now for easier compatibility w/ PHPUnit!
	*/
	public function toString() {
		return $this->getMessage();
	}
}