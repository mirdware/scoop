<?php
class Helper {
	/*Metodo que realiza la redirección permanente de ciertas paginas*/
	public static function redirect ($url) {
		header('HTTP/1.0 301 Moved Permanently');
		header ( 'Location:'.$url );
		exit;
	}
	
	/*
		Metodo para la generación de cadenas de string aleatorias.
		$opt=>uc = (UperCase) des/activar la generación con mayusculas, por defecto lo hace.
		$opt=>n = (Numbers) des/activar la generación con numeros, por defecto lo hace.
		$opt=>sc = (SpecialChars) des/activa la generación con caracteres especiales, por defecto NO lo hace.
	*/
	public static function strRandom($length=10, $opt=array()) {
		$opt = array_merge(array('uc'=>true, 'n'=>true, 'sc'=>false), $opt);
		$source = 'abcdefghijklmnopqrstuvwxyz';
		if($opt['uc']==1) $source .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
		if($opt['n']==1) $source .= '1234567890';
		if($opt['sc']==1) $source .= '|@#~$%()=^*+[]{}-_';
		if($length>0){
			$rstr = "";
			$source = str_split($source,1);
			for($i=1; $i<=$length; $i++){
				mt_srand((double)microtime() * 1000000);
				$num = mt_rand(1,count($source));
				$rstr .= $source[$num-1];
			}

		}
		return $rstr;
	}
	
	/*
		Algoritmo para verificar si una clave es lo suficientemente segura
		* Debe tener caracteres numericos y no numericos
		* Debe tener tanto mayusculas como minisculas
		* Debe tener algun tipo de caracter especial
		* Dependiendo del tamaño se considera más o menos segura
	*/
	public static function esClaveSegura($clave) {
		$cont = 0;
		$csize = strlen($clave);
		if ($csize!=0){
			if (preg_match('/\d/', $clave) && preg_match('/\D/', $clave)){
				$cont += 20;
			}
			if (preg_match('/[a-z]/', $clave) && preg_match('/[A-Z]/', $clave)){
				$cont += 20;
			}
			if(preg_match('/(\s|\\|\/|!|"|·|\$|%|&|\(|\)|=|\?|¿|\||@|#|¬|€|\^|\`|\[|\]|\+|\*|¨|\´|\{|\}|\-|_|\.|:|,|;|>|<)/', $clave)) {
				$cont += 20;
			}
			if ($csize >= 4 && $csize <= 5){
				$cont += 10;
			} elseif ($csize >= 6 && $csize <= 8){
				$cont+= 30;
			} elseif ($csize > 8){
				$cont += 40;
			}
		}
		return ($cont>50);
	}
	
	/*Sistema de __autolad manejado por el bootstrap*/
	public static function autoload ($name) {
		$load = 'models/'.$name.'.php';
		if (!is_readable($load)) {
			$load = 'controllers/'.$name.'.php';
			if(!is_readable($load)) {
				$load = 'library/'.$name.'/'.$name.'.php';
			}
		}
        if (is_readable($load)) {
        	require $load;
        }
	}
}
?>