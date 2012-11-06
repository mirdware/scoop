<?php
/**
	* Clase conexion que sirve para enlazar la base de datos con
	* la aplicación y abstraer las funciones que dependen de cada
	* DBMS.
	* Autor: Marlon Ramirez
	* Version: 0.8
	* DBMS: postgreSQL
**/

class Conexion {
	//conexion persistente a la base de datos
	private $conex;
	//Lista de conexiones existentes en la ejecución de la aplicación
	private static $instances = array();
	
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
	public static function get ($db = '', $user = '', $pass = '', $host = '') {
		$key = $db.$user.$pass.$host;
		if (!isset(self::$instances[$key])) {
			self::$instances[$key] = new Conexion($db, $user, $pass, $host);
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
		$r = pg_query($this->conex, $this->filterXSS($consulta));
		
		if(strpos(strtoupper($consulta), 'SELECT') === 0) {
			$res = new Result($r);
			return $res;
		} else {
			return $r;
		}
	}
	
	public function error(){
		return pg_last_error($this->conex);
	}
	
	public function escape($val) {
		if ($val === NULL) {
			return 'NULL';
		}
		if (get_magic_quotes_gpc()) {
        	$val = stripslashes($val);
		}
		$val = "'" . pg_escape_string($val) . "'";
		return $val;
	}
	
	public function lastId() {
		return pg_last_oid($this->conex);
	}
	
	/*Función especial para realizar filtrado xss*/
	private function filterXSS($val) {
		$val = preg_replace('/([\x00-\x08][\x0b-\x0c][\x0e-\x20])/', '', $val);
		
		$search = 'abcdefghijklmnopqrstuvwxyz';
		$search .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$search .= '1234567890!@#$%^&*()';
		$search .= '~`";:?+/={}[]-_|\'\\';
		for ($i = 0; $i < strlen($search); $i++) {
			$val = preg_replace('/(&#[x|X]0{0,8}'.dechex(ord($search[$i])).';?)/i', $search[$i], $val);
			$val = preg_replace('/(&#0{0,8}'.ord($search[$i]).';?)/', $search[$i], $val);
			$ra1 = Array('javascript', 'vbscript', 'expression', 'applet', 'meta', 'xml', 'blink', 'link', 'style', 'script', 'embed', 'object', 'iframe', 'frame', 'frameset', 'ilayer', 'layer', 'bgsound', 'title', 'base');
			$ra2 = Array('onabort', 'onactivate', 'onafterprint', 'onafterupdate', 'onbeforeactivate', 'onbeforecopy', 'onbeforecut', 'onbeforedeactivate', 'onbeforeeditfocus', 'onbeforepaste', 'onbeforeprint', 'onbeforeunload', 'onbeforeupdate', 'onblur', 'onbounce', 'oncellchange', 'onchange', 'onclick', 'oncontextmenu', 'oncontrolselect', 'oncopy', 'oncut', 'ondataavailable', 'ondatasetchanged', 'ondatasetcomplete', 'ondblclick', 'ondeactivate', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onerror', 'onerrorupdate', 'onfilterchange', 'onfinish', 'onfocus', 'onfocusin', 'onfocusout', 'onhelp', 'onkeydown', 'onkeypress', 'onkeyup', 'onlayoutcomplete', 'onload', 'onlosecapture', 'onmousedown', 'onmouseenter', 'onmouseleave', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onmove', 'onmoveend', 'onmovestart', 'onpaste', 'onpropertychange', 'onreadystatechange', 'onreset', 'onresize', 'onresizeend', 'onresizestart', 'onrowenter', 'onrowexit', 'onrowsdelete', 'onrowsinserted', 'onscroll', 'onselect', 'onselectionchange', 'onselectstart', 'onstart', 'onstop', 'onsubmit', 'onunload');
			$ra = array_merge($ra1, $ra2);
			$found = TRUE;
			while ($found == TRUE) {
				$val_before = $val;
				for ($i = 0, $l = count($ra); $i < $l; $i++) {
					$pattern = '/';
					for ($j = 0; $j < strlen($ra[$i]); $j++) {
						if ($j > 0) {
							$pattern .= '(';
							$pattern .= '(&#[x|X]0{0,8}([9][a][b]);?)?';
							$pattern .= '|(&#0{0,8}([9][10][13]);?)?';
							$pattern .= ')?';
						}
						$pattern .= $ra[$i][$j];
					}
					$pattern .= '/i';
					$replacement = substr($ra[$i], 0, 2).'<x>'.substr($ra[$i], 2);
					$val = preg_replace($pattern, $replacement, $val);
					if ($val_before == $val) {
						$found = FALSE;
					}
				}
			}
			return $val;
		}
	}
}

//**********************************************************************************

class Result {
	private $res;
	
	public function __construct($res) {
		$this->res = $res;
	}
	
	public function __destruct() {
		if(!is_null($this->res)){
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
	
	public function result($pos) {
		return pg_fetch_result($this->res,$pos);
	}
	
	public function reset() {
		pg_result_seek($this->res, 0);
	}
}