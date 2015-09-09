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
    private $assets;
    /**
     * Objeto que se encarga del enrutamiento dentro de la aplicación.
     */
    private $router;

    /**
     * Establece la configuración inicial de los atributos del Helper
     * @param string $name Nombre de la vista actual.
     * @param Message $msg Mensaje de la vista actual.
     */
    public function __construct($name, $msg)
    {
        $config = \Scoop\IoC\Service::getInstance('config');
        $this->name = $name;
        $this->msg = $msg;
        $this->router = $config->getRouter();
        $this->assets = (array) $config->get('asset') + array(
            'path' => 'public/',
            'img' => 'images/',
            'css' => 'css/',
            'js' => 'js/'
        );
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
        return ROOT.$this->assets['path'].$resource;
    }

    /**
     * Obtiene la ruta configurada hasta el path de imagenes.
     * @param string $image Nombre de la imagen a obtener.
     * @return string ruta a la imagen especificada.
     */
    public function img($image)
    {
        return $this->overt($this->assets['img'].$image);
    }

    /**
     * Obtiene la ruta configurada hasta el path de hojas de estilos.
     * @param string $styleSheet Nombre del archivo css a obtener.
     * @return string ruta a la hoja de estilos especificada.
     */
    public function css($styleSheet)
    {
        return $this->overt($this->assets['css'].$styleSheet);
    }

    /**
     * Obtiene la ruta configurada hasta el path de javascript.
     * @param string $image Nombre del archivo javascript a obtener.
     * @return string ruta al archivo javascript especificada.
     */
    public function js($javaScript)
    {
        return $this->overt($this->assets['js'].$javaScript);
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
            throw new \Exception('Unsoported number of arguments');
        }
        $args = func_get_args();
        return ROOT.$this->router->getURL(array_shift($args), $args);
    }
}
