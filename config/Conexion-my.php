<?php
/**
	* Clase conexion que sirve para enlazar la base de datos con
	* la aplicación y abstraer las funciones que dependen de cada
	* DBMS.
	* Autor: Marlon Ramirez
	* Version: 0.6
	* DBMS: MySQL
**/

class Conexion {
	private $conex;
	private static $instances = array();
	
	/*constructor*/
	private function __construct($db, $user, $pass, $host) {
		$this->conex = mysql_connect($host, $user, $pass, true) or die($this->error());
		//selecciona el cotejamiento de la base de datos
		$this->query('SET NAMES \'utf8\''); 
		mysql_select_db($db,$this->conex) or exit($this->error());
	}
	
	public function __destruct(){
		if ($this->conex) {
			mysql_close($this->conex);
		}
	}
	
	private function __clone(){ }
	
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
			return false;
		}
		$consulta = trim($consulta);
		//echo $consulta;
		$r = mysql_query($this->filterXSS($consulta), $this->conex);
		
		if(strpos(strtoupper($consulta), 'SELECT') === 0) {
			$res = new Result($r);
			return $res;
		} else {
			return $r;
		}
	}
	
	public function error(){
		return mysql_error($this->conex);
	}
	
	public function escape($val) {
		if (get_magic_quotes_gpc()) {
        	$val = stripslashes($val);
		}
		
		if (!is_numeric($val)) {
			$val = "'" . mysql_real_escape_string($val) . "'";
		}
		return $val;
	}
	
	public function lastId() {
		return mysql_insert_id($this->conex);
	}
	
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
			$found = true;
			while ($found == true) {
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
						$found = false;
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
		if($this->res){
			mysql_free_result($this->res);
		}
	}
	
	/*abstraccion de los metodos independiente del DBMS*/
	public function numRows() {
		return mysql_num_rows($this->res);
	}
	
	public function toObject() {
		return mysql_fetch_object($this->res);
	}
	
	public function toArray() {
		return mysql_fetch_array($this->res);
	}
	
	public function toAssoc() {
		return mysql_fetch_assoc($this->res);
	}
	
	public function result($pos) {
		return mysql_result($this->res,$pos);
	}
	
	public function reset() {
		mysql_data_seek($this->res, 0);
	}
}
?>