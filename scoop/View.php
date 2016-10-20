<?php
namespace Scoop;

/**
 * La función principal de esta clase es la de asociar los controladores con
 * sus respectivos templates.
 */
final class View
{
    /** Ruta donde se encuentran las vistas. */
    const ROOT = 'app/cache/views/';
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
    private $viewPath;
    private $currentComponents = array();
    private static $components = array();

    /**
     * Genera la vista partiendo desde el nombre de la misma.
     * @param $viewPath nombre de la vista.
     */
    public function __construct($viewPath)
    {
        $this->viewData = array();
        $this->viewPath = $viewPath;
        foreach (self::$components as $key => &$component) {
            $this->currentComponents[$key] = new $component();
        }
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
     * @return View La instancia de la clase para encadenamiento.
     */
    public function remove($keys = null)
    {
        if (!$keys) {
            $this->viewData = array();
            return $this;
        }
        if (is_array($keys)) {
            foreach ($keys as &$key) {
                unset($this->viewData[$key]);
            }
            return $this;
        }
        unset($this->viewData[$keys]);
        return $this;
    }

    public function __call($method, $args)
    {
        $array = preg_split('/(?=[A-Z])/', $method);
        $component = strtolower(array_pop($array));
        if (isset(self::$components[$component])) {
            $method = join($array);
            call_user_func_array(array($this->currentComponents[$component], $method), $args);
            return $this;
        }
        throw new \BadMethodCallException('Component '.$component.' unregistered');
    }

    /**
     * Compila la vista para devolver un String formateado en HTML.
     * @return string Formato en HTML.
     * @throws \Exception Si no se existe la plantilla o la vista.
     */
    public function render()
    {
        $helperView = new View\Helper($this->currentComponents);
        IoC\Service::register('view', $helperView);
        View\Heritage::init($this->viewData);
        View\Template::parse($this->viewPath);
        extract($this->viewData);
        include self::ROOT.$this->viewPath.self::EXT;
        $view = ob_get_contents().\Scoop\View\Heritage::getFooter();
        ob_end_clean();
        return $view;
    }

    public static function registerComponent($name, $class)
    {
        self::$components[$name] = $class;
    }
}
