<?php
namespace Scoop;

abstract class Controller
{
    private $router;

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

    /**
     * Obtiene la instancia de un controlador diferente al actual
     * @param  String $controller Nombre del controlador a obtener
     * @return Controller             Controlador a obtener
     */
    protected function getController($controller)
    {
        return $this->router->single($controller);
    }

    /**
     * Inyeccta la dependencia por set para el router
     * @param Bootstrap\Router $router Router que se encargara de obtener los controladores
     */
    public function setRouter (Bootstrap\Router $router)
    {
        $this->router = $router;
    }

    /**
     * Realiza la redirección permanente de ciertas páginas
     * @param  String $url Dirección a la que se redirecciona la página
     */
    public static function redirect($url)
    {
        header('HTTP/1.0 301 Moved Permanently');
        header ( 'Location:'.$url );
        exit;
    }

    /**
     * Debe ser implementado en cada controlador y se encarga  de realizar 
     * la tarea por defecto cuando no se encuentra un método de dicho controlador
     * @param  array  $args Argumentos pasados al controlador
     */
    public abstract function get(array $args);
}
