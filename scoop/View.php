<?php
namespace scoop;
/**
 * Clase encargada de manejar la vista
 */
class View {
	//ruta donde se encuentran las vistas
	const ROOT_VIEWS = 'app/views/';
	//ruta donde se encuentran las platillas
	const ROOT_TEMPLATES = 'app/templates/';
	//extención de los archivos que funcionan como vistas
	const EXT_VIEWS = '.php';
	//extención de los archivos que funcionan como plantillas
	const EXT_TEMPLATES = '.sdt.php';
	//viewData que contiene los datos a ser procesados por la vista
	private $viewData;
	//Muestra el mensaje, puede ser de tipo error, out, alert
	private $msg;
	//Nombre de la vista
	private $viewName;
	//activar layers
	private $activeLayer;
	protected static $flagLayer;

	public function __construct ( $viewName ) {
		$this->viewData = array();
		$this->activeLayer = TRUE;
		$this->msg = '<div id="msg-not"></div>';
		$this->viewName = $viewName;
	}
	
	/**
	 * Establece o modifica los datos que va a procesar la vista.
	 * @param string|array $key   Nombre de la variable dentro de la vista.
	 * @param [type] $value Valor de la variable.
	 * @return  View retorna para encadenamiento
	 */
	public function set ($key, $value=NULL) {
		if(is_array($key)) {
			$this->viewData += $key;
		} else {
			$this->viewData[$key] = $value;
		}
		return $this;
	}
	
	/**
	 * Remueve un dato de la vista o en su defecto, reinicia la misma.
	 * @param  string|array|null $key dependiendo del tipo de dato elimina una o varias variables
	 *                                de la vista.
	 * @return  View retorna para encadenamiento
	 */
	public function remove ($key=FALSE) {
		if($key) {
			if ( is_array($key) ) {
				foreach ($key as &$v) {
					unset($this->viewData[$k]);
				}
			} else {
				unset($this->viewData[$key]);
			}
		} else {
			$this->viewData = array();
		}
		return $this;
	}

	public function get () {
		$this->generate();
		$view = ob_get_contents();
		ob_end_clean();
		return $view;
	}

	public function showLayer ( $activeLayer ) {
		$this->activeLayer = $activeLayer;
	}
	
	/* Configura el mensaje que sera mostrado en el sistema de notificaciones interno */
	public function setMessage ($msg, $type = 'out') {
		if ($type !== 'error' && $type !== 'out' && $type !== 'alert') {
			throw new Exception("Error building only accepted message types: error, out and alert.", 1);
		}
		$this->msg = '<div id="msg-'.$type.'">'.$msg.'</div>';
		return $this;
	}

	public function pushMessage ($msg, $type = 'out') {
		$_SESSION['msg-scoop'] = array('type'=>$type, 'msg'=>$msg);
		return $this;
	}

	public function pullMessage () {
		if (isset($_SESSION['msg-scoop'])) {
			$this->setMessage($_SESSION['msg-scoop']['msg'], $_SESSION['msg-scoop']['type']);
			unset($_SESSION['msg-scoop']);
		}
		return $this;
	}
	
	/* Configura los datos para que coincidan con el sistema interno de errores */
	public function setErrors ($array) {
		foreach ($array as $key=>$value) {
			$array[$key] = 'style = "visibility: visible" title = "'.$value.'"';
		}
		$this->set($array);
		return $this;
	}

	/*Renderiza la vista con los datos suminitrados*/
	public function render () {
		$this->generate();
		ob_end_flush();
	}

	private function generate () {
		$this->viewData += array(
			'msg_scoop' => $this->msg
		);
		//cambio el flag del layer al del objeto
		self::$flagLayer = $this->activeLayer;
		extract ($this->viewData);
		include self::ROOT_VIEWS.$this->viewName.self::EXT_VIEWS;
	}
	
}