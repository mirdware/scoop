<?php
namespace Scoop;

/**
 * Clase encargada de asociar los controladores con sus respectivos templates.
 */
final class View
{
    /**
     * Ruta desde la que se puede ubicar la vista.
     * @var string
     */
    private $viewPath;
    /**
     * Contiene los datos a ser procesados por la vista.
     * @var array
     */
    private $viewData = array();
    /**
     * Colección con los componentes que hacen parte de la vista.
     * @var array
     */
    private static $components = array(
        'message' => '\Scoop\View\Message'
    );

    /**
     * Genera la vista partiendo desde el nombre de la misma.
     * @param $viewPath nombre de la vista.
     */
    public function __construct($viewPath)
    {
        $this->viewPath = $viewPath;
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
     * @param  string|array|null $keys Dependiendo del tipo elimina uno o
     * varios datos.
     * @return Scoop\View La instancia de la clase para encadenamiento.
     */
    public function remove($keys = null)
    {
        if (!$keys) {
            $this->viewData = array();
            return $this;
        }
        if (is_array($keys)) {
            foreach ($keys as $key) {
                unset($this->viewData[$key]);
            }
            return $this;
        }
        unset($this->viewData[$keys]);
        return $this;
    }

    /**
     * Compila la vista para devolver un String formateado en HTML.
     * @return string Formato en HTML.
     */
    public function render()
    {
        $helperView = new View\Helper(self::$components);
        \Scoop\Context::registerService('view', $helperView);
        View\Heritage::init($this->viewData);
        View\Template::parse($this->viewPath, $this->viewData);
        return View\Heritage::getContent();
    }

    /**
     * Convierte el llamado a un componente previamente registrado.
     * @param  string $method Nombre del método llamado.
     * @param  array $args   Argumentos pasados al metodo.
     * @return \Scoop\View   La instancia de la clase para encadenamiento.
     */
    public function __call($method, $args)
    {
        $array = preg_split('/(?=[A-Z])/', $method);
        $component = strtolower(array_pop($array));
        if (isset(self::$components[$component])) {
            $method = join($array);
            call_user_func_array(array(self::$components[$component], $method), $args);
            return $this;
        }
        throw new \BadMethodCallException('Component '.$component.' unregistered');
    }

    /**
     * Registra los componentes que seran utilizados en la vista.
     * @param  string $name  Nombre con el que se identificara el componente internamente.
     * @param  string $className Referencia a la clase que sera componentizada.
     */
    public static function registerComponent($name, $className)
    {
        $componentInterface = 'Scoop\View\Component';
        if (!is_subclass_of($className, $componentInterface)) {
            throw new \UnexpectedValueException($className.' not implement '.$componentInterface);
        }
        self::$components[strtolower($name)] = $className;
    }
}
