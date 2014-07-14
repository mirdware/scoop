<?php
namespace scoop\bootstrap;

abstract class App {
	const MAIN_CONTROLLER = 'Home';
	const MAIN_METHOD = 'main';

	public static function run () {
		if (substr($_SERVER['REQUEST_URI'], -9) === 'index.php') {
			\scoop\Controller::redirect ( str_replace('index.php', '', $_SERVER['REQUEST_URI']) );
		}

		Config::init();
		Config::add('app/config');

		/*Ruta por defecto*/
		$class = self::MAIN_CONTROLLER;
		$method = self::MAIN_METHOD;
		$params = array();
		$validURL = TRUE;

		/*Sanear variables por POST y GET*/
		if ( $_POST ) {
			self::purgePOST( $_POST );
		}
		if ($_GET) {
			self::purgeGET( $_GET );
		}

		if( isset($_GET['route']) ) {
			/*saneo las variables que vienen por url y libero route del array $_GET*/
			$url = filter_input (INPUT_GET, 'route', FILTER_SANITIZE_STRING);
			unset( $_GET['route'] );
			if ( strtolower($url) !== $url ) {
				throw new \scoop\http\NotFoundException();
			}
			$url = array_filter ( explode( '/', $url ) );
			
			/*Configurando clase, metodo y parametros según la url*/
			$class = str_replace( ' ', '', //une las palabras
				ucwords( //convierte las primeras letras de las palabras a mayúscula
					str_replace( '-', ' ', //convierte cada - a un espacio
						array_shift($url) //obtiene el primer parámetro
					)
				)
			);

			if ($url) {
				$method = explode( '-',
					array_shift($url)
				);
				//uso a $params como auxiliar
				$params = array_shift( $method );
				if (empty($method)) {
					$method = $params;
				} else {
					$method = $params.implode( array_map('ucfirst', $method) );
				}

				$validURL = ($method !== self::MAIN_METHOD);
			} elseif ($class === self::MAIN_CONTROLLER) {
				\scoop\Controller::redirect(ROOT);
			}

			$params = $url;
			unset ($url);
		}

		/*generando la reflexión sobre el controlador*/
		if ( $validURL && is_readable(\scoop\Controller::ROOT.$class.'.php') ) {
			//$auxReflection = la reflexión de la clase para poder explorarla
			$class = str_replace('/', '\\', \scoop\Controller::ROOT).$class;
			$auxReflection = new \ReflectionClass( $class );
			if ($auxReflection->hasMethod( $method )) {
				$method = $auxReflection->getMethod( $method );
				/*$auxReflection = el número de elementos de $param, 
				para no tener que llamar 2 veces la funcion count*/
				$auxReflection = count ($params);
				if ($auxReflection >= $method->getNumberOfRequiredParameters() && $auxReflection <= $method->getNumberOfParameters()) {
					//$auxReflection = lo que se mostrara al finalizar aplicación
					$auxReflection = $method->invokeArgs(new $class, $params);
					exit ($auxReflection);
				}
			}
		}

		throw new \scoop\http\NotFoundException();
	}

	private static function purgePOST ( &$post ) {
		foreach ($post as $key => $value) {
			if (is_array($value)) {
				restructurePOST($value);
			} else {
				$post[$key] = self::filterXSS( trim($value) );
			}
		}
	}

	private static function purgeGET ( &$get ) {
		foreach ($get as $key => $value) {
			if (is_array($value)) {
				restructureGET($value);
			} else {
				//<htmlentities> dentro del POST va a ser suprimida en proximas versiones
				$get[$key] = htmlspecialchars( trim($value) , ENT_QUOTES, 'UTF-8');
			}
		}
	}

	/**
	 * Función para filtrar XSS tomada de https://gist.github.com/mbijon/1098477
	 * @param string $data 
	 * @return Datos filtrados
	 */
	private static function filterXSS ( $data ) {
		// Fix &entity\n;
		$data = str_replace(array('&amp;','&lt;','&gt;'), array('&amp;amp;','&amp;lt;','&amp;gt;'), $data);
		$data = preg_replace('/(&#*\w+)[\x00-\x20]+;/u', '$1;', $data);
		$data = preg_replace('/(&#x*[0-9A-F]+);*/iu', '$1;', $data);
		$data = html_entity_decode($data, ENT_COMPAT, 'UTF-8');

		// Remove any attribute starting with "on" or xmlns
		$data = preg_replace('#(<[^>]+?[\x00-\x20"\'])(?:on|xmlns)[^>]*+>#iu', '$1>', $data);

		// Remove javascript: and vbscript: protocols
		$data = preg_replace('#([a-z]*)[\x00-\x20]*=[\x00-\x20]*([`\'"]*)[\x00-\x20]*j[\x00-\x20]*a[\x00-\x20]*v[\x00-\x20]*a[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2nojavascript...', $data);
		$data = preg_replace('#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*v[\x00-\x20]*b[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2novbscript...', $data);
		$data = preg_replace('#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*-moz-binding[\x00-\x20]*:#u', '$1=$2nomozbinding...', $data);

		// Only works in IE: <span style="width: expression(alert('Ping!'));"></span>
		$data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?expression[\x00-\x20]*\([^>]*+>#i', '$1>', $data);
		$data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?behaviour[\x00-\x20]*\([^>]*+>#i', '$1>', $data);
		$data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:*[^>]*+>#iu', '$1>', $data);

		// Remove namespaced elements (we do not need them)
		$data = preg_replace('#</*\w+:\w[^>]*+>#i', '', $data);

		do
		{
			// Remove really unwanted tags
			$old_data = $data;
			$data = preg_replace('#</*(?:applet|b(?:ase|gsound|link)|embed|frame(?:set)?|i(?:frame|layer)|l(?:ayer|ink)|meta|object|s(?:cript|tyle)|title|xml)[^>]*+>#i', '', $data);
		}
		while ($old_data !== $data);

		// we are done...
		return $data;
	}
	
}