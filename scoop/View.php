<?php
namespace Scoop;
/**
 * La función principal de esta clase es la de asociar los controladores 
 * con sus respectivos templates.
 */
class View
{
    //ruta donde se encuentran las vistas
    const ROOT = 'app/views/php/';
    //extensión de los archivos que funcionan como vistas
    const EXT = '.php';
    //viewData que contiene los datos a ser procesados por la vista
    private $viewData;
    //Nombre de la vista
    private $viewName;
    //Muestra el mensaje, puede ser de tipo error, out, alert
    public $msg;

    public function __construct($viewName)
    {
        $this->viewData = array();
        $this->msg = new __Message__();
        $this->viewName = $viewName;
    }

    /**
     * Modifica los datos que va a procesar la vista
     * @param String|Array $key   Identificador del dato en la vista,
     * si es un array se ejecuta el par clave => valor
     * @param Mixed $value Dato a procesar por la vista
     */
    public function set($key, $value=null)
    {
        if (is_array($key)) {
            $this->viewData += $key;
        } else {
            $this->viewData[$key] = $value;
        }
        return $this;
    }

    /**
     * Remueve un dato de la vista o en su defecto, reinicia la misma.
     * @param  String|Array|null $key dependiendo del tipo elimina uno o varios datos
     * de la vista.
     */
    public function remove($key=false)
    {
        if ($key) {
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

    /**
     * @deprecated
     * @param array Array con los errores a mostrar
     * @return View
     */
    public function setErrors($array)
    {
        foreach ($array as $key=>$value) {
            $array[$key] = 'style = "visibility: visible" title = "'.$value.'"';
        }
        $this->set($array);
        return $this;
    }

    public function render()
    {
        \Scoop\View\Helper::init(array(
            'name' => &$this->viewName,
            'msg' => $this->msg
        ));
        \Scoop\View\Heritage::init($this->viewData);
        \Scoop\View\Template::parse($this->viewName);
        extract($this->viewData);
        include self::ROOT.$this->viewName.self::EXT;
        $view = ob_get_contents().\Scoop\View\Heritage::getFooter();
        ob_end_clean();
        return $view;
    }

}

final class __Message__
{
    private $msg;
    private $type;

    public function __construct()
    {
        $this->msg = '<div id="msg-not"></div>';
    }

    private function validate(&$type)
    {
        $this->type = $type;
        if ($this->type !== 'error' &&
            $this->type !== 'out' &&
            $this->type !== 'alert') {
            throw new Exception("Error building only accepted message types: error, out and alert.", 1);
        }
    }

    private function apply()
    {
        $this->msg = '<div id="msg-'.$this->type.'">'.$this->msg.'</div>';
    }

    /* Configura el mensaje que sera mostrado en el sistema de notificaciones interno */
    public function set($msg, $type = 'out')
    {
        $this->msg = $msg;
        $this->validate($type);
        $this->apply();
        return $this;
    }

    public function push($msg, $type = 'out')
    {
        $this->validate($type);
        $_SESSION['msg-scoop'] = array('type'=>$type, 'msg'=>$msg);
        return $this;
    }

    public function pull()
    {
        if (isset($_SESSION['msg-scoop'])) {
            $this->type = $_SESSION['msg-scoop']['type'];
            $this->msg = $_SESSION['msg-scoop']['msg'];
            $this->apply();
            unset($_SESSION['msg-scoop']);
        }
        return $this;
    }

    public function __toString()
    {
        return $this->msg;
    }
}
