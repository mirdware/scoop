<?php
namespace Scoop\View;

/**
 * Sirve como contenedor de funciones utiles a la vista.
 */
class Helper
{
    /**
     * @var string Nombre de la vista actual.
     */
    private $name;
    /**
     * @var Message Mensaje que maneja la vista
     */
    private $msg;
    /**
     * @var array Ubicación de los assets dentro de la aplicación
     */
    private static $assets = array(
        'path' => 'public/',
        'img' => 'images/',
        'css' => 'css/',
        'js' => 'js/'
    );

    /**
     * Establece la configuración inicial de los atributos del Helper
     * @param string $name Nombre de la vista actual.
     * @param Message $msg Mensaje de la vista actual.
     */
    public function __construct($name, $msg)
    {
        $this->name = $name;
        $this->msg = $msg;
    }

    public static function setAssets($assets)
    {
        self::$assets = (array) $assets + self::$assets;
    }

    /**
     * Obtiene el nombre de la vista actual.
     * @return string Nombre de la vista actual.
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Obtiene el mensaje de la vista actual.
     * @return Message Mensaje de la vista actual.
     */
    public function getMsg()
    {
        return $this->msg;
    }

    /**
     * Obtiene la ruta configurada hasta el path publico.
     * @param string $resource Nombre del recurso a obtener.
     * @return string ruta al recurso especificado.
     */
    public function overt($resource)
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
        return $this->overt(self::$assets['img'].$image);
    }

    /**
     * Obtiene la ruta configurada hasta el path de hojas de estilos.
     * @param string $styleSheet Nombre del archivo css a obtener.
     * @return string ruta a la hoja de estilos especificada.
     */
    public function css($styleSheet)
    {
        return $this->overt(self::$assets['css'].$styleSheet);
    }

    /**
     * Obtiene la ruta configurada hasta el path de javascript.
     * @param string $image Nombre del archivo javascript a obtener.
     * @return string ruta al archivo javascript especificada.
     */
    public function js($javaScript)
    {
        return $this->overt(self::$assets['js'].$javaScript);
    }

    /**
     * Obtiene la URL formateada según la ruta y parametros enviados.
     * @param mixed $args Recibe como parametros ($nombreRuta, $params...).
     * @return string URL formateada según el nombre de la ruta y los parámetros
     * enviados.
     */
    public function route()
    {
        if (func_num_args() === 0) {
            throw new \InvalidArgumentException('Unsoported number of arguments');
        }
        $args = func_get_args();
        $router = \Scoop\IoC\Service::getInstance('config')->getRouter();
        return $router->getURL(array_shift($args), $args);
    }
}
