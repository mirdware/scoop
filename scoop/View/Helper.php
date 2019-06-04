<?php
namespace Scoop\View;

/**
 * Clase que sirve como contenedor de funciones utiles a la vista.
 */
class Helper
{
    /**
     * Componentes que podra manejar las vistas.
     * @var array
     */
    private $components;
    /**
     * Inyección del servicio config.
     * @var \Scoop\Bootstrap\Environment
     */
    private $config;
    /**
     * Ubicación de los assets dentro de la aplicación.
     * @var array
     */
    private static $assets = array(
        'path' => 'public/',
        'img' => 'images/',
        'css' => 'css/',
        'js' => 'js/'
    );

    /**
     * Establece la configuración inicial de los atributos del Helper
     * @param array $components colección de componentes usados por la vista.
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

    /**
     * Compone la clase dependiendo de los parametros dados.
     * @return string Estructura HTML del componente generado.
     */
    public function compose()
    {
        if (func_num_args() === 0) {
            throw new \InvalidArgumentException('Unsoported number of arguments');
        }
        $args = func_get_args();
        $component = strtolower(array_shift($args));
        $component = new \ReflectionClass($this->components[$component]);
        $component = $component->newInstanceArgs($args);
        return $component->render();
    }
}
