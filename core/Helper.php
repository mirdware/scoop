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
		$opt = array_merge(array('uc'=>TRUE, 'n'=>TRUE, 'sc'=>FALSE), $opt);
		$source = 'abcdefghijklmnopqrstuvwxyz';
		if($opt['uc']==1) $source .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
		if($opt['n']==1) $source .= '1234567890';
		if($opt['sc']==1) $source .= '|@#~$%()=^*+[]{}-_';
		if($length>0){
			$rstr = '';
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
	public static function isSafePassword($clave) {
		$len = strlen($clave);
		$numbers = '01234567890';
		$lcLetters = 'abcdefghijklmnñopqrstuvwxyza';
		$ucLetters = 'ABCDEFGHIJKLMNÑOPQRSTUVWXYZA';
		$iNumbers = '98765432109';
		$iLcLetters = 'zyxwvutsrrqpoñnmlkjihgfedcbaz';
		$iUcLetters = 'ZYXWVUTSRRQPOÑNMLKJIHGFEDCBAZ';
		$chars = '';
		$ucChar = 0;
		$lcChar = 0;
		$numChar = 0;
		$spChar = 0;
		$cucChar = 0;
		$clcChar = 0;
		$cnumChar = 0;
		$charRep = 0;
		$cons = 0;
		$only = 0;

		for ($i=0, $charc, $prev='', $union; $i<$len; $i++) {
			$charc = $clave[$i];
			$union = $prev.$charc;

			if ( strpos($numbers, $union) !== FALSE
				|| strpos($ucLetters, $union) !== FALSE
				|| strpos($lcLetters, $union) !== FALSE
				|| strpos($iNumbers, $union) !== FALSE
				|| strpos($iUcLetters, $union) !== FALSE
				|| strpos($iLcLetters, $union) !== FALSE ) {
				$cons++;
			}

			if ( strpos($chars, $charc) === FALSE ) {
				$chars .= $charc;
			} else {
				$charRep++;
			}

			if ( strpos($numbers, $charc) !== FALSE ) {
				if ( $prev && strpos($numbers, $prev) !== FALSE ) {
					$cnumChar++;
				}
				$numChar++;
			} elseif ( strpos($ucLetters, $charc) !== FALSE ) {
				if ( $prev && strpos($ucLetters, $prev) !== FALSE ) {
					$clcChar++;
				}
				$lcChar++;
			} elseif ( strpos($lcLetters, $charc) !== FALSE ) {
				if ( $prev && strpos($lcLetters, $prev) !== FALSE ) {
					$cucChar++;
				}
				$ucChar++;
			} else {
				$spChar++;
			}

			$prev = $charc;
		}

		if ( ($lcChar+$ucChar) == $len || $numChar == $len ) {
			$only = $len;
		}
		if ($ucChar) {
			$ucChar = (($len-$ucChar)*3);
		}
		if ($lcChar) {
			$lcChar = (($len-$lcChar)*3);
		}

		$total = ($len*7)+$ucChar+$lcChar+($numChar*4)+($spChar*5)
				-$only-($charRep*3)-($cucChar*2)-($cnumChar*2)-($clcChar*2)-($cons*5);

		return ($total>=60);
	}
	
	/*Sistema de __autolad manejado por el bootstrap*/
	public static function autoload ($name) {
		$routes = array(
			'models/'.$name.'.php',
			'library/'.$name.'/'.$name.'.php',
			'controllers/'.$name.'.php'
		);
		$load = FALSE;

		foreach ($routes as $route) {
			if ( is_readable($route) ) {
				$load = $route;
				break;
			}
		}

        if ($load) {
        	require $load;
        }
	}

	public static function sendMail($subject, $message, $from, $to, $opt=array()) {
		/*Estableciendo variables*/
		$docsNames = NULL;
		$fBody = NULL;
		$cc = isset($opt['cc'])? $opt['cc'] : '';
		$cco = isset($opt['cco'])? $opt['cco'] : '';
		$reply = isset($opt['reply']) ?$opt['reply'] : '';
		$format = isset($opt['format']) ?$opt['format'] : 'html';
		$attach = isset($opt['attach'])? $opt['attach'] : array();
		$separator =  uniqid('MirdWare');
		unset ($opt);
		foreach ($attach as $file) {
      		if($file['size']!=0) {
      			if( !isset($file['data']) ) {
      				$file['data']=fread(fopen($file['tmp_name'], 'r'),$file['size']);
      			}
      			$docsNames.= "X-attachments: ".$file['name']."\n";
      			$fBody	.= "\n--$separator\n"
						.	"Content-type: ".$file['type']."; name=\"".$file['name']."\"\n"
						.	"Content-Transfer-Encoding: BASE64\n"
						.	"Content-disposition: attachment; filename=\"".$file['name']."\"\n\n"
						.	chunk_split(base64_encode($file['data']))."\n";
			}
		}
		/* Aplicando Cabezeras al Mensaje*/
		$headers = "From: ".$from."\n";
		if($cc) {
			$headers .= "CC: ".$cc."\n";
		}
		if($cco) {
			$headers .= "BCC: ".$cco."\n";
		}
		if($reply) {
			$headers .= "Reply-To: ".$reply."\n";
		}
		$headers .= "X-Priority: 1\n"
				.	"X-MSMail-Priority: High\n"
				.	"X-Mailer: std.php\n"
				.	"Return-Path: ".$from."\n"
				.	"MIME-version: 1.0\n"
				.	"Content-type: multipart/mixed; boundary=\"$separator\"\n"
				.	"Content-transfer-encoding: 7BIT\n".$docsNames;
        /* Comienzo del Cuerpo del Mensaje*/
   		$body = "--$separator\n"
			.	"Content-type: text/".$format."; charset=UTF-8\n"
			.	"Content-transfer-encoding: 7BIT\n"
			.	"Content-description:Cuerpo de Mensaje\n\n"
			.	$message."\n\n".$fBody."--$separator--\n";
   		/* Enviando el Mensaje*/
   		return mail($to,'=?ISO-8859-1?B?'.base64_encode(utf8_decode($subject)).'=?=',$body,$headers);
	}
	
}