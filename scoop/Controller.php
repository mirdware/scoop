<?php
namespace Scoop;
/* Clase Controladora que inplementa a la vista, es decir exige la implementación de un metodo principal (main) */
abstract class Controller
{
    private $router;

    /*Verifica si la pagina fue llamada via ajax o normalmente*/
    protected function ajax()
    {
        return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest');
    }

    protected function notFound($msg = null)
    {
        throw new \Scoop\Http\NotFoundException($msg);
    }

    protected function accessDenied($msg = null)
    {
        throw new \Scoop\Http\AccessDeniedException($msg);
    }

    protected function getController($controller)
    {
        return $this->router->single($controller);
    }

    public function setRouter (Bootstrap\Router $router)
    {
        $this->router = $router;
    }

    /*Metodo que realiza la redirección permanente de ciertas paginas*/
    public static function redirect($url)
    {
        header('HTTP/1.0 301 Moved Permanently');
        header ( 'Location:'.$url );
        exit;
    }

    public abstract function get(array $args);
}
