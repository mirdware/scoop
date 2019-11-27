<?php
namespace Scoop\View;

class Helper
{
    private $components;
    private $config;
    private static $assets = array(
        'path' => 'public/',
        'img' => 'images/',
        'css' => 'css/',
        'js' => 'js/'
    );

    /**
     * Establece la configuración inicial de los atributos del Helper
     * @param array<\Scoop\View\Component> $components colección de componentes usados por la vista.
     */
    public function __construct($components)
    {
        $this->components = $components;
        $this->config = \Scoop\Context::getService('config');
        self::$assets = (array) $this->config->get('assets') + self::$assets;
    }

    /**
     * Obtiene la ruta configurada hasta el path publico.
     * @param string $resource Nombre del recurso a obtener.
     * @return string ruta al recurso especificado.
     */
    public function asset($resource)
    {
        return ROOT.self::$assets['path'].$resource;
    }

    /**
     * Obtiene la ruta configurada hasta el path de imagenes.
     * @param string $image Nombre de la imagen a obtener.
     * @return string ruta a la imagen especificada.
     */
    public function img($image)
    {
        return $this->asset(self::$assets['img'].$image);
    }

    /**
     * Obtiene la ruta configurada hasta el path de hojas de estilos.
     * @param string $styleSheet Nombre del archivo css a obtener.
     * @return string ruta a la hoja de estilos especificada.
     */
    public function css($styleSheet)
    {
        return $this->asset(self::$assets['css'].$styleSheet);
    }

    /**
     * Obtiene la ruta configurada hasta el path de javascript.
     * @param string $javaScript Nombre del archivo javascript a obtener.
     * @return string ruta al archivo javascript especificada.
     */
    public function js($javaScript)
    {
        return $this->asset(self::$assets['js'].$javaScript);
    }

    /**
     * Obtiene la URL formateada según la ruta y parametros enviados.
     * @param mixed $args Recibe como parametros ($nombreRuta, $params...).
     * @return string URL formateada según el nombre de la ruta y los parámetros
     * enviados.
     */
    public function route()
    {
        return $this->config->getURL(func_get_args());
    }

    public function addPage($data, $quantity)
    {
        $queryString = \Scoop\Context::getService('request')->getQuery();
        $nextPage = $data['page'] + $quantity;
        if ($nextPage < 0 || $nextPage * $data['size'] >= $data['total']) {
            return $this->route();
        }
        $queryString['page'] = $nextPage;
        return $this->route($queryString);
    }

    /**
     * Compone la clase dependiendo de los parametros dados.
     * @return string Estructura HTML del componente generado.
     */
    public function __call($method, $args)
    {
        if (strpos($method, 'compose') === 0) {
            $component = lcfirst(substr($method, 7));
            if (isset($this->components[$component])) {
                $component = new \ReflectionClass($this->components[$component]);
                $component = $component->newInstanceArgs($args);
                return Template::clearHTML($component->render());
            }
            throw new \BadMethodCallException('Component '.$component.' unregistered');
        }
        trigger_error('Call to undefined method '.__CLASS__.'::'.$method.'()', E_USER_ERROR);
    }
}
