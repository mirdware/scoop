<?php
class Home extends Controller{
	private static $view;
	
	public function __construct () {
		self::$view = 'Home';
		if ($this->ajax()) {
			$this->showLayer (false);
		} else {
			$this->setView('title', 'EPS - IPS');
		}
	}
	
	public function save () {
		if (empty($_POST['nombre'])) {
			return 'falta nombre';
		}
		if (empty($_POST['nombres'])) {
			return 'falta nombres';
		}
		if (empty($_POST['apellidos'])) {
			return 'falta apellidos';
		}
		if (empty($_POST['direccion'])) {
			return 'falta direccion';
		}
		if (!Usuario::exist($_POST['nombre'])) {
			$user = Usuario::create($_POST)->read()->toObject();
			return utf8_encode('Nombre: '.$user->nombre.
					'<br>Nombres: '.$user->nombres.
					'<br>Apellidos: '.$user->apellidos.
					'<br>Dirección: '.$user->direccion);
		} else {
			$user = new Usuario ($_POST['nombre']);
			$user = $user->read()->toObject();
			return utf8_decode('Nombre: '.$user->nombre.
					'<br>Nombres: '.$user->nombres.
					'<br>Apellidos: '.$user->apellidos.
					'<br>Dirección: '.$user->direccion);
		}
	}
	
	public function hola ($msj, $type='out') {
		$this->message($type, urldecode($msj));
		$this->render(self::$view);
	}
	
	public static function main () {
		$home = new Home ();
		$home->render(self::$view);
	}
}
?>