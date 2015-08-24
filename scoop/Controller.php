<?php
namespace Scoop;

abstract class Controller
{
    private $router;
    private static $redirects = array(
        300 => 'HTTP/1.1 300 Multiple Choices',
        301 => 'HTTP/1.1 301 Moved Permanently',
        302 => 'HTTP/1.1 302 Found',
        303 => 'HTTP/1.1 303 See Other',
        304 => 'HTTP/1.1 304 Not Modified',
        305 => 'HTTP/1.1 305 Use Proxy',
        306 => 'HTTP/1.1 306 Not Used',
        307 => 'HTTP/1.1 307 Temporary Redirect'
    );

    /**
     * Inyecta la dependencia router al controlador
     * @param IoC\Router $router Router que se encargara de obtener los controladores
     */
    public function setRouter(IoC\Router $router)
    {
        $this->router = $router;
    }

    /**
     * Realiza la redirección a la página pasada como parámetro
     * @param  String $url Dirección a la que se redirecciona la página
     */
    public static function redirect($url, $status = 301)
    {
        header(self::$redirects[$status], true, $status);
        header('Location:'.$url);
        exit;
    }

    /**
     * Verifica si la pagina fue llamada via ajax o normalmente
     * @return boolean Devuelve true si la página fue llamada via ajax y false en caso contrario
     */
    protected function ajax()
    {
        return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest');
    }

    /**
     * Emite una excepción 404 desde el controlador
     * @param  String $msg Mensaje enviado a la excepción
     */
    protected function notFound($msg = null)
    {
        throw new \Scoop\Http\NotFoundException($msg);
    }

    /**
     * Emite una excepción 403 desde el controlador
     * @param  String $msg Mensaje enviado a la excepción
     */
    protected function accessDenied($msg = null)
    {
        throw new \Scoop\Http\AccessDeniedException($msg);
    }

    protected function error($msg)
    {
        throw new \Scoop\Http\BadRequestException($msg);
    }

    /**
     * Obtiene la instancia del controlador ligado a la ruta
     * @param  String            $controller Nombre del controlador a obtener
     * @return \Scoop\Controller             Controlador a obtener
     */
    protected function getController($controller)
    {
        return $this->router->getInstance($controller);
    }

    /**
     * Obtiene el servicio especificado por el usuario
     * @param String $serviceName Nombre del servicio a obtener
     * @return Object Servicio a obtener
     */
    protected function getService($serviceName)
    {
        return \Scoop\IoC\Service::getInstance($serviceName);
    }

    /**
     * Debe ser implementado en cada controlador y se encarga  de realizar
     * la tarea por defecto cuando no se encuentra el método
     * @param  array  $args Argumentos pasados al controlador
     */
    public abstract function get(array $args);
}
