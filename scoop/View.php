<?php
namespace Scoop;

/**
 * La función principal de esta clase es la de asociar los controladores con
 * sus respectivos templates.
 */
final class View
{
    /** Ruta donde se encuentran las vistas. */
    const ROOT = 'app/views/php/';
    /**
     * Extensión de los archivos que funcionan como vistas.
     */
    const EXT = '.php';
    /**
     * @var string Contiene los datos a ser procesados por la vista.
     */
    private $viewData;
    /**
     * @var string Nombre de la vista.
     */
    private $viewName;
    /**
     * @var View\Message Muestra el mensaje, puede ser de tipo out, warning, error.
     */
    private $msg;

    /**
     * Genera la vista partiendo desde el nombre de la misma.
     * @param $viewName nombre de la vista.
     */
    public function __construct($viewName)
    {
        $this->viewData = array();
        $this->msg = new View\Message();
        $this->viewName = $viewName;
    }

    /**
     * Modifica los datos que va a procesar la vista.
     * @param string|array $key   Identificador del dato en la vista, si es un
     * array se ejecuta el par clave => valor.
     * @param mixed $value Dato a procesar por la vista.
     * @return View La instancia de la clase para encadenamiento.
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
     * @param  string|array|null $arrayKeys Dependiendo del tipo elimina uno o
     * varios datos.
     * @return View La instancia de la clase para encadenamiento.
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
     * Valida y muestra el mensaje suministrado por el usuario.
     * @param string $msg  Mensaje a ser mostrado por la aplicación.
     * @param string $type Tipo de mensaje a mostrar.
     * @return View La instancia de la clase para encadenamiento.
     */
    public function setMessage($msg, $type = View\Message::OUT)
    {
        $this->msg->set($msg, $type);
        return $this;
    }

    /**
     * Valida y guarda el mensaje suministrado por el usuario.
     * @param  string $msg Mensaje a ser mostrado por la aplicación.
     * @param  string $type Tipo de mensaje a mostrar (out, warning, error).
     * @return View La instancia de la clase para encadenamiento.
     */
    public function pushMessage($msg, $type = View\Message::OUT)
    {
        $this->msg->push($msg, $type);
        return $this;
    }

    /**
     * Muestra y elimina el mensjae suministrado por el usuario.
     * @return View La instancia de la clase para encadenamiento.
     */
    public function pullMessage()
    {
        $this->msg->pull();
        return $this;
    }

    /**
     * Compila la vista para devolver un String formateado en HTML.
     * @return string Formato en HTML.
     * @throws \Exception Si no se existe la plantilla o la vista.
     */
    public function render()
    {
        if (!is_readable(self::ROOT.$this->viewName.self::EXT) &&
        !is_readable(View\Template::ROOT.$this->viewName.View\Template::EXT)) {
            throw new \Exception('It has not been possible to load the template or view');
        }
        $helperView = new \Scoop\View\Helper($this->viewName, $this->msg);
        \Scoop\IoC\Service::register('view', $helperView);
        \Scoop\View\Heritage::init($this->viewData);
        \Scoop\View\Template::parse($this->viewName);
        extract($this->viewData);
        include self::ROOT.$this->viewName.self::EXT;
        $view = ob_get_contents().\Scoop\View\Heritage::getFooter();
        ob_end_clean();
        return $view;
    }
}
