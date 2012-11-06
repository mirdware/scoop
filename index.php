<?php
/*importando archivos de configuración*/
require 'core/Helper.php';
require 'core/Conexion.php';
require 'core/MVC.php';

/*redireccion de index*/
if (substr($_SERVER['REQUEST_URI'], -9) === 'index.php') {
	helper::redirect ( str_replace('index.php', '', $_SERVER['REQUEST_URI']) );
}

/*definicion de constantes globales*/
define ('ROOT', 'http://' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . '/');
define ('APP_NAME', 'bootstrap std - MirdWare');
define ('APC', TRUE);

/*configuración*/
spl_autoload_register('Helper::autoload');
setlocale(LC_ALL,"es_ES@euro","es_ES","esp");
date_default_timezone_set('America/Bogota');
session_start();

/*Ruta por defecto*/
$class = 'home';
$method = 'main';
$params = array();
$validMethod = TRUE;
if( $_GET ) {
	/*sanatizo las variables que vienen por url y libero route del array $_GET*/
	$url = filter_input (INPUT_GET, 'route', FILTER_SANITIZE_STRING);
	unset( $_GET['route'] );
	$url = array_filter ( explode( '/', $url ) );
	
	/*Configurando clase, metodo y parametros según la url*/
	$class = strtolower( array_shift($url) );
	if ($url) {
		$method = strtolower( array_shift($url) );
		$validMethod = ($method != 'main');
	} elseif ($class == 'home') {
		Helper::redirect(ROOT);
	}

	$params = $url;
	unset ($url);
}

/*Sanitizando variables por post*/
if ( $_POST ) {
	foreach ($_POST as $key => $value) {
		$_POST[$key] = htmlentities( trim($_POST[$key]) , ENT_QUOTES, 'UTF-8');
	}
}

/*generando la reflexion sobre el controlador*/
if ( $validMethod && is_readable('controllers/'.$class.'.php') ) {
	require 'controllers/'.$class.'.php';
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

/*Ejecuta una rutina de error*/
require 'controllers/Error.php';
$class = new Error ();
$class->main();