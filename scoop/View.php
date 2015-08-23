<?php
namespace Scoop;

/**
 * La función principal de esta clase es la de asociar los controladores con sus respectivos templates.
 */
final class View
{
    //ruta donde se encuentran las vistas
    const ROOT = 'app/views/php/';
    //extensión de los archivos que funcionan como vistas.
    const EXT = '.php';
    //viewData que contiene los datos a ser procesados por la vista.
    private $viewData;
    //Nombre de la vista
    private $viewName;
    //Muestra el mensaje, puede ser de tipo error, out, alert.
    public $msg;

    public function __construct($viewName)
    {
        $this->viewData = array();
        $this->msg = new __Message__();
        $this->viewName = $viewName;
    }

    /**
     * Modifica los datos que va a procesar la vista.
     * @param string|array $key   Identificador del dato en la vista, si es un array se ejecuta el par clave => valor.
     * @param mixed        $value Dato a procesar por la vista
     * @return \Scoop\View        La instancia de la clase para encadenamiento.
     */
    public function set($key, $value = null)
    {
        if (is_array($key)) {
            $this->viewData += $key;
            return $this;
        }
        $this->viewData[$key] = $value;
        return $this;
    }

    /**
     * Remueve un dato de la vista o en su defecto reinicia la misma.
     * @param  string|array|null $arrayKeys Dependiendo del tipo elimina uno o varios datos.
     * @return \Scoop\View                  La instancia de la clase para encadenamiento.
     */
    public function remove($arrayKeys = null)
    {
        if ($arrayKeys) {
            if (!is_array($arrayKeys)) {
                $arrayKeys = array($arrayKeys);
            }
            foreach ($arrayKeys as &$key) {
                unset($this->viewData[$key]);
            }
            return $this;
        }
        $this->viewData = array();
        return $this;
    }

    /**
     * Compila la vista para devolver un String formateado en HTML.
     * @return string Formato en HTML.
     */
    public function render()
    {
        \Scoop\IoC\Service::register('view',
            new \Scoop\View\Helper($this->viewName, $this->msg)
        );
        \Scoop\View\Heritage::init($this->viewData);
        \Scoop\View\Template::parse($this->viewName);
        extract($this->viewData);
        include self::ROOT.$this->viewName.self::EXT;
        $view = ob_get_contents().\Scoop\View\Heritage::getFooter();
        ob_end_clean();
        return $view;
    }

    /**
     * Verifica si existe la vista, ya sea template o vista compilada.
     * @return boolean Existe la vista o no.
     */
    public function there() {
        return is_readable(self::ROOT.$this->viewName.self::EXT) || 
                is_readable(View\Template::ROOT.$this->viewName.View\Template::EXT);
    }
}

final class __Message__
{
    private $msg;

    public function __construct()
    {
        $this->msg = '<div id="msg-not"></div>';
    }

    /**
     * Valida y guarda el mensaje suministrado por el usuario.
     * @param  string              $msg  Mensaje a ser mostrado por la aplicación.
     * @param  string              $type Tipo de mensaje a mostrar (out, alert, error).
     * @return \Scoop\__Message__        La instancia de la clase para encadenamiento.
     */
    public function push($msg, $type = 'out')
    {
        self::validate($type);
        $_SESSION['msg-scoop'] = array('type'=>$type, 'msg'=>$msg);
        return $this;
    }

    /**
     * Muestra y elimina el mensjae suministrado por el usuario
     * @return \Scoop\__Message__ La instancia de la clase para encadenamiento.
     */
    public function pull()
    {
        if (isset($_SESSION['msg-scoop'])) {
            $this->setMsg($_SESSION['msg-scoop']['type'], $_SESSION['msg-scoop']['msg']);
            unset($_SESSION['msg-scoop']);
        }
        return $this;
    }

    /**
     * Valida y muestra el mensaje suministrado por el usuario
     * @param string               $msg  Mensaje a ser mostrado por la aplicación.
     * @param string               $type Tipo de mensaje a mostrar.
     * @return \Scoop\__Message__        La instancia de la clase para encadenamiento.
     */
    public function set($msg, $type = 'out')
    {
        self::validate($type);
        $this->setMsg($type, $msg);
        return $this;
    }

    /**
     * Muestra el mensaje
     * @return string Mensaje usado por el usuario
     */
    public function __toString()
    {
        return $this->msg;
    }

    private function setMsg($type, $msg)
    {
        $this->msg = '<div id="msg-'.$type.'">'.$msg.'</div>';
    }

    private static function validate($type)
    {
        if ($this->type !== 'error' &&
            $this->type !== 'out' &&
            $this->type !== 'alert') {
            throw new Exception("Error building only accepted message types: error, out and alert.", 1);
        }
    }
}
