<?php

namespace Scoop;

final class View
{
    private $viewPath;
    private $viewData;

    /**
     * Genera la vista partiendo desde el nombre de la misma.
     * @param string $viewPath nombre de la vista.
     */
    public function __construct($viewPath)
    {
        $this->viewPath = $viewPath;
        $this->viewData = array();
    }

    /**
     * Modifica los datos que va a procesar la vista.
     * @param string|array<string, string|array> $key Identificador del dato en la vista, si es un
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
        View\Service::inject('view', \Scoop\Context::inject('\Scoop\View\Helper'));
        View\Service::inject('config', \Scoop\Context::inject('\Scoop\Bootstrap\Environment'));
        View\Heritage::init();
        extract($this->viewData);
        require View\Heritage::getCompilePath($this->viewPath);
        return View\Heritage::getContent();
    }
}
