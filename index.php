<?php
session_start();

/*importando archivos de configuración*/
require 'config/Helper.php';
require 'config/Conexion.php';
require 'config/MVC.php';

/*redireccion de index*/
$index = substr($_SERVER['REQUEST_URI'], -10);
if ($index != 'index.html') {
	$index = substr($_SERVER['REQUEST_URI'], -9);
	if ($index != 'index.php' && $index != 'index.htm') {
		$index = false;
	}
}

if( $index ) {
	helper::redirect ( str_replace($index, '', $_SERVER['REQUEST_URI']) );
}
unset ($index);

/*definicion de constantes*/
define ('ROOT', 'http://' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . '/');

spl_autoload_register('Helper::autoload');
/*configuración regional*/
setlocale(LC_ALL,"es_ES@euro","es_ES","esp");
date_default_timezone_set('America/Bogota');

/*Ruta por defecto*/
$class = 'Home';
$method = 'main';
$params = array();
if( $_GET ) {
	/*sanatizo las variables que vienen por url y libero route del array $_GET*/
	$url = filter_input (INPUT_GET, 'route', FILTER_SANITIZE_STRING);
	unset( $_GET['route'] );
	/*
		$last contendra el ultimo caracter de la variable en caso de no ser un / se redirecciona a la misma
		dirección pero incluyendo el / al final de la cadena.
	*/
	$last = strlen($url) - 1;
	if ($url[$last] != '/') {
		helper::redirect ($_SERVER['REQUEST_URI'].'/');
	}
	$url = array_filter ( explode( '/', $url ) );
	unset ($last);
	
	/*Configurando clase, metodo y parametros según la url*/
	$class = array_shift($url);
	if ($url) {
		$method = array_shift($url);
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
if ( is_readable('controllers/'.$class.'.php') ) {
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
?>