<?php

namespace Scoop;

final class View
{
    private $viewPath;
    private $viewData = array();
    private static $request;
    private static $components = array(
        'message' => '\Scoop\View\Message'
    );

    /**
     * Genera la vista partiendo desde el nombre de la misma.
     * @param string $viewPath nombre de la vista.
     */
    public function __construct($viewPath)
    {
        $this->viewPath = $viewPath;
    }

    /**
     * Modifica los datos que va a procesar la vista.
     * @param string|array<string> $key   Identificador del dato en la vista, si es un
     * array se ejecuta el par clave => valor.
     * @param mixed $value Dato a procesar por la vista.
     * @return \Scoop\View La instancia de la clase para encadenamiento.
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
     * @param  string|array<string>|null $keys Dependiendo del tipo elimina uno o varios datos.
     * @return \Scoop\View La instancia de la clase para encadenamiento.
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
        $helperView = new View\Helper(self::$request, self::$components);
        View\Service::inject('view', $helperView);
        View\Service::inject('config', \Scoop\Context::getEnvironment());
        View\Heritage::init($this->viewData);
        extract($this->viewData);
        require View\Heritage::getCompilePath($this->viewPath);
        return View\Heritage::getContent();
    }

    public static function setRequest(\Scoop\Http\Request $request)
    {
        foreach (self::$components as $className) {
            $reflectionClass = new \ReflectionClass($className);
            if ($reflectionClass->hasMethod('setRequest')) {
                $reflectionMethod = $reflectionClass->getMethod('setRequest');
                if ($reflectionMethod->isStatic()) {
                    $reflectionMethod->invoke($reflectionClass, $request);
                }
            }
        }
        self::$request = $request;
    }

    /**
     * Convierte el llamado a un componente previamente registrado.
     * @param  string $method Nombre del método llamado.
     * @param  array<mixed> $args Argumentos pasados al metodo.
     * @return \Scoop\View La instancia de la clase para encadenamiento.
     */
    public static function __callStatic($method, $args)
    {
        $array = preg_split('/(?=[A-Z])/', $method);
        $component = strtolower(array_pop($array));
        if (isset(self::$components[$component])) {
            $method = implode('', $array);
            return call_user_func_array(array(self::$components[$component], $method), $args);
        }
        throw new \BadMethodCallException('Component ' . $component . ' unregistered');
    }

    /**
     * Registra los componentes que seran utilizados en la vista.
     * @param  string $name Nombre con el que se identificara el componente internamente.
     * @param  string $className Referencia a la clase que sera componentizada.
     */
    public static function registerComponents($components)
    {
        foreach ($components as $name => $className) {
            $componentInterface = 'Scoop\View\Component';
            if (!is_subclass_of($className, $componentInterface)) {
                throw new \InvalidArgumentException($className . ' not implement ' . $componentInterface);
            }
            self::$components[strtolower($name)] = $className;
        }
    }
}
