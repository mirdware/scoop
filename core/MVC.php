<?php
/*
	Interfaz del Modelo, trabaja sobre una filosofia CRUD-E
		Create: Generar objetos del modelo, insertandolos en la base de datos.
		Read: Obtiene de la base de datos los atributos que le son pasados en el array.
		Update: Actualiza la base de datos segun la información contenida en el array asociativo.
		Delete: Elimina el objeto de la base de datos.
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
	private $msg = '<div id="msg-not"></div>';
	//Atributo que define si una vista va a tener Layer o no
	private $renderLayer = TRUE;
	//Layer por defecto que sera mostrado por la aplicación
	private $layer = 'layer'; 
	
	/*Establece o modifica los datos que va a procesar la vista*/
	protected function setView ($key, $value=NULL) {
		if(is_array($key)) {
			foreach ($key as $k=>$v) {
				$this->setView($k, $v);
			}
		} else {
			$this->hashMap['${'.$key.'}'] = $value;
		}
	}
	
	/*Remueve un dato de la vista o en su defecto, reinicia la misma*/
	protected function removeView ($key=FALSE) {
		if($key) {
			unset($this->hashMap[$key]);
		} else {
			$this->hashMap = array();
		}
	}
	
	/* Configura el mensaje que sera mostrado en el sistema de notificaciones interno */
	protected function showMessage ($msg, $type = 'out') {
		if ($type !== 'error' && $type !== 'out' && $type !== 'alert') {
			throw new Exception("Error building only accepted message types: error, out and alert.", 1);
		}
		$this->msg = '<div id="msg-'.$type.'">'.$msg.'</div>';
	}

	protected function pushMessage ($msg, $type = 'out') {
		$_SESSION['msg-scoop'] = array('type'=>$type, 'msg'=>$msg);
	}

	protected function pullMessage () {
		if (isset($_SESSION['msg-scoop'])) {
			$this->showMessage($_SESSION['msg-scoop']['msg'], $_SESSION['msg-scoop']['type']);
			unset($_SESSION['msg-scoop']);
		}
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
		$key = 'layers-'.$this->layer;

		//opteniendo los layers por APC o sesión
		if (APC) {
			apc_delete($key);//eliminar en producción
			if ( apc_exists($key) ) {
				return apc_fetch($key);
			}
		} elseif ( isset($_SESSION[$key]) ) {
			return $_SESSION[$key];
		}
		//ubicacion completa del archivo
		$file = 'views/layers/'.$this->layer.'.html';
		$layer = file_get_contents($file);
		if (APC) {
			apc_store($key, $layer, 1200);
		}
		return $layer;
	}

	protected function setLayer($layer) {
		$this->layer = $layer;
	}

	/*Renderiza la vista con los datos suminitrados*/
	public function render ($vista, $return=FALSE) {
		$template = file_get_contents('views/'.$vista.'.html');
		$layer = $this->getLayer();

		if($layer && $this->renderLayer) {
			$template = str_replace('${page}', $template, $layer);
		}
		//constantes SCOOP
		$this->hashMap = array_merge($this->hashMap, array(
			'${root}' => ROOT,
			'${msg-scoop}' => $this->msg
		));
		
		$template = str_replace(
			array_keys($this->hashMap),
			array_values($this->hashMap),
			$template
		);

		if ($return) {
			return $template;
		}
		echo $template;
	}
	
}