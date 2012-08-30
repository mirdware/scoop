<?php
/*
	Interfaz del Modelo, trabaja sobre una filosofia CRUD-E
		Create: Generar objetos del modelo, insertandolos en la base de datos.
		Read: Obtiene de la base de datos los atributos que le son pasados en el array.
		Update: Actualiza la base de datos segun la información contenida en el array asociativo.
		Delete: Elimina el objeto de la base de datos.
		Exist: Verifica si un dato existe dentro de la base de datos o no.
*/
interface Model {
	public static function create($array);
	public function read($array=array());
	public function update($array);
	public function delete();
}

/* Interfaz de la vista que exige, se tenga un metodo principal */
interface View {
	public function main();
}

/* Clase Controladora que inplementa a la vista, es decir exige la implementación de un metodo principal (main) */
abstract class Controller implements View {
	//HashMap que contiene los datos a ser procesados por la vista
	private $hashMap = array();
	//Muestra el mensaje, puede ser de tipo error u out
	private $msg = '<div id="msg"></div>';
	//Atributo que define si una vista va a tener Layer o no
	private $renderLayer = true;
	//Layer por defecto que sera mostrado por la aplicación
	private $layer = 'layer';
	
	/*Establece o modifica los datos que va a procesar la vista*/
	protected function setView ($key, $value=NULL) {
		if(is_array($key)) {
			$this->hashMap = array_merge ($this->hashMap, $key);
		} else {
			$this->hashMap[$key] = $value;
		}
	}
	
	/*Remueve un dato de la vista o en su defecto, reinicia la misma*/
	protected function removeView ($key=false) {
		if($key) {
			unset($this->hashMap[$key]);
		} else {
			$this->hashMap = array();
		}
	}
	
	/* Configura el mensaje que sera mostrado en el sistema de notificaciones interno */
	protected function message ($type, $msg) {
		$this->msg = '<div id="'.$type.'">'.$msg.'</div>';
	}
	
	/* Configura los datos para que coincidan con el sistema interno de errores */
	protected function showErrors ($array) {
		foreach ($array as $key=>$value) {
			$array[$key] = 'style = "visibility: visible" title = "'.$value.'"';
		}
		$this->setView($array);
	}

	/*Verifica si la pagina fue llamada via ajax o normalmente*/
	protected function ajax() {
		return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest');
	}

	/*Establece si se debe mostrar o no el layer*/
	public function showLayer($val) {
		if(is_bool($val)) {
			$this->renderLayer = $val;
		}
	}
	
	/*
		Obtiene el layer a renderizar. Esto es asi ya que el mismo layer puede tener datos dinamicos que
		deben ser procesados una vez. Por lo cual se procesan dentro de esta función, evitando asi la
		sobrecarga de trabajo.
	*/
	private function getLayer() {
		if (!isset($_SESSION['layer'])) {
			$_SESSION['layer'] = array();
		}
		if (!isset($_SESSION['layer'][$this->layer])) {
			//ubicacion completa del archivo
			$file = 'views/layers/'.$this->layer.'.html';
			$_SESSION['layer'][$this->layer] = file_get_contents($file);
		}
		return $_SESSION['layer'][$this->layer];
		//return file_get_contents('views/layers/'.$this->layer.'html');
	}

	protected function setLayer($layer) {
		$this->layer = $layer;
	}

	/*Renderiza la vista con los datos suminitrados*/
	public function render ($vista, $return=false) {
		$template = file_get_contents('views/'.$vista.'.html');
		$layer = $this->getLayer();

		if($layer && $this->renderLayer) {
			$template = str_replace('{page}', $template, $layer);
		}
		
		foreach ($this->hashMap as $clave=>$valor) {
			$template = str_replace('{'.$clave.'}', $valor, $template);
		}
		
		$template = str_replace('{root}', ROOT, $template);
		$template = str_replace('{msg}', $this->msg, $template);

		if ($return) {
			return $template;
		}
		echo $template;
	}
	
}
?>