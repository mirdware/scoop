<?php
/**
 * Scoop (Simple Characteristics of Object Oriented PHP) apoya el uso de convenciones PHP.
 * Clases: PascalCase <http://localhost/class-to-pascal-case/>
 * Métodos: camelCase <http://localhost/class-to-pascal-case/method-to-camel-case/>
 * constantes: ALL_CAPS
 * Namespace / Package: small_caps
 * Propiedades: camelCase
 * Párametro: camelCase
 * Variable: camelCase
 * Interface: PascalCase
 * Usa PHP como si se tratase de un lenguaje case sensitive.
 *
 * @package Scoop
 * @author  Marlon Ramirez <marlonramirez@outlook.com>
 */

try {
	require 'scoop/bootstrap/UniversalClassLoader.php';
	$loader = new UniversalClassLoader();
	$loader->useIncludePath( TRUE );
	$loader->register();

	if (substr($_SERVER['REQUEST_URI'], -9) === 'index.php') {
		\scoop\controller::redirect ( str_replace('index.php', '', $_SERVER['REQUEST_URI']) );
	}

	set_error_handler( function ($code, $error, $file = NULL, $line = NULL) {
		throw new Exception( $error );
	} );

	/*definicion de constantes globales*/
	define ('ROOT', '//'.$_SERVER['HTTP_HOST'].rtrim(dirname($_SERVER['PHP_SELF']), '/\\').'/');
	define ('APP_NAME', 'SCOOP');
	define ('APP_EMAIL', 'mirdware@gmail.com');
	define ('APC', extension_loaded('apc'));
	define ('DB_SCHEMA', '');//valido para bases de datos con esquemas
	define ('DEFAULT_CLASS', 'Home');
	define ('DEFAULT_METHOD', 'main');
	define ('NS_CONTROLLERS', 'app\controllers\\');

	/*configuración*/
	setlocale(LC_ALL,"es_ES@euro","es_ES","esp");
	date_default_timezone_set('America/Bogota');
	//ini_set('display_errors', '0');// descomentar en producción
	session_start();

	/*Ruta por defecto*/
	$class = NS_CONTROLLERS.DEFAULT_CLASS;
	$method = DEFAULT_METHOD;
	$params = array();
	$validURL = TRUE;

	if( isset($_GET['route']) ) {
		/*sanatizo las variables que vienen por url y libero route del array $_GET*/
		$url = filter_input (INPUT_GET, 'route', FILTER_SANITIZE_STRING);
		unset( $_GET['route'] );
		$url = array_filter ( explode( '/', $url ) );
		
		/*Configurando clase, metodo y parametros según la url*/
		$class = NS_CONTROLLERS.str_replace( ' ', '', //une las palabras
			ucwords( //combierte las primeras letras de las palabras a mayuscula
				str_replace( '-', ' ', //convierte cada - a un espacio
					strtolower( //pasa a minuscula todo el string (en estudio)
						array_shift($url) //optiene el primer parametro
					)
				)
			)
		);

		if ($url) {
			$method = explode( '-',
				strtolower(
					array_shift($url)
				)
			);
			//uso a $params como auxiliar
			$params = array_shift( $method );
			if (empty($method)) {
				$method = $params;
			} else {
				$method = $params.implode( array_map('ucfirst', $method) );
			}

			$validURL = ($method !== DEFAULT_METHOD);
		} elseif ($class === NS_CONTROLLERS.DEFAULT_CLASS) {
			\scoop\controller::redirect(ROOT);
		}

		$params = $url;
		unset ($url);
	}

	/*Sanitizando variables por POST y GET*/
	if ( $_POST ) {
		foreach ($_POST as $key => $value) {
			//htmlentities dentro del post va a ser suprimida en proximas versiones
			$_POST[$key] = htmlentities( trim($_POST[$key]) , ENT_QUOTES, 'UTF-8');
		}
	}
	if ($_GET) {
		foreach ($_GET as $key => $value) {
			$_GET[$key] = htmlentities( trim($_GET[$key]) , ENT_QUOTES, 'UTF-8');
		}
	}

	/*generando la reflexion sobre el controlador*/
	$path = str_replace('\\', DIRECTORY_SEPARATOR, $class).'.php';
	if ( $validURL && is_readable($path) ) {
		//$auxReflection = la reflexion de la clase para poder explorarla
		$auxReflection = new ReflectionClass( $class );
		if ($auxReflection->hasMethod( $method )) {
			$method = $auxReflection->getMethod( $method );
			//$auxReflection = el numero de elementos de param, para no tener que llamar 2 veces la funcion count
			$auxReflection = count ($params);
			if ($auxReflection >= $method->getNumberOfRequiredParameters() && $auxReflection <= $method->getNumberOfParameters()) {
				//$auxReflection = lo que se mostrara al finalizar aplicación
				$auxReflection = $method->invokeArgs(new $class, $params);
				exit ($auxReflection);
			}
		}
	}
	throw new \scoop\http\NotFoundException();

} catch (\scoop\http\Exception $ex) {
	$ex->handler();
}