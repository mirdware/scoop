<?php
namespace scoop\bootstrap;

abstract class App {
	public static function run () {
		if (substr($_SERVER['REQUEST_URI'], -9) === 'index.php') {
			\scoop\controller::redirect ( str_replace('index.php', '', $_SERVER['REQUEST_URI']) );
		}

		Config::init();
		Config::add('app/config');

		/*Ruta por defecto*/
		$class = Config::get('app.default.class');
		$method = Config::get('app.default.method');
		$params = array();
		$validURL = TRUE;

		if( isset($_GET['route']) ) {
			/*sanatizo las variables que vienen por url y libero route del array $_GET*/
			$url = filter_input (INPUT_GET, 'route', FILTER_SANITIZE_STRING);
			unset( $_GET['route'] );
			$url = array_filter ( explode( '/', $url ) );
			
			/*Configurando clase, metodo y parametros según la url*/
			$class = str_replace( ' ', '', //une las palabras
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

				$validURL = ($method !== Config::get('app.default.method'));
			} elseif ($class === Config::get('app.default.class')) {
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
		if ( $validURL && is_readable(\scoop\Controller::ROOT.$class.'.php') ) {
			//$auxReflection = la reflexion de la clase para poder explorarla
			$class = str_replace('/', '\\', \scoop\Controller::ROOT).$class;
			$auxReflection = new \ReflectionClass( $class );
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
	}
}