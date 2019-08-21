<?php
namespace Scoop;

abstract class Controller
{
    private static $redirects = array(
        300 => 'HTTP/1.1 300 Multiple Choices',
        301 => 'HTTP/1.1 301 Moved Permanently',
        302 => 'HTTP/1.1 302 Found',
        303 => 'HTTP/1.1 303 See Other',
        304 => 'HTTP/1.1 304 Not Modified',
        305 => 'HTTP/1.1 305 Use Proxy',
        306 => 'HTTP/1.1 306 Not Used',
        307 => 'HTTP/1.1 307 Temporary Redirect',
        308 => 'HTTP/1.1 308 Permanent Redirect'
    );

    /**
     * Realiza la redirección a la página pasada como parámetro.
     * @param string $url Dirección a la que se redirecciona la página.
     * @param integer $status Código de la redirección que se va a realizar.
     */
    public static function redirect($url, $status = 302)
    {
        header(self::$redirects[$status], true, $status);
        if (is_array($url)) {
            $config = \Scoop\Context::getService('config');
            $url = $config->getURL($url);
        }
        header('Location:'.$url);
        exit;
    }

    /**
     * Retorna al usuario a la página anterior.
     */
    protected function goBack()
    {
        self::redirect($_SERVER['HTTP_REFERER']);
    }

    /**
     * Atajo para lanzar una excepción 404 desde el controlador.
     * @param string $msg Mensaje enviado a la excepción.
     * @throws \Scoop\Http\NotFoundException
     */
    protected function notFound($msg = null)
    {
        throw new \Scoop\Http\NotFoundException($msg);
    }

    /**
     * Atajo para lanzar una excepción 403 desde el controlador.
     * @param string $msg Mensaje enviado a la excepción.
     * @throws \Scoop\Http\accessDeniedException
     */
    protected function denyAccess($msg = null)
    {
        throw new \Scoop\Http\AccessDeniedException($msg);
    }

    /**
     * Ejecuta las validaciones y si no pasan se lanza una excepción 400.
     * @param \Scoop\Validator $validator Objeto que contiene las validaciones a realizar.
     * @param array<mixed> $data contiene los datos a ser validados.
     * @throws \Scoop\Http\BadRequestException
    */
    protected function validate($validator, $data)
    {
        $errors = $validator->validate($data);
        if (empty($errors)) return;
        $request = $this->inject('request');
        $_SESSION['data-scoop'] = array(
            'body' => $request->getBody(),
            'query' => $request->getQuery(),
            'error' => $errors
        );
        throw new \Scoop\Http\BadRequestException(json_encode($errors));
    }

    /**
     * Obtiene el servicio especificado por el usuario.
     * @param string $serviceName Nombre del servicio a obtener.
     * @return object Servicio a obtener.
     */
    protected function inject($serviceName)
    {
        return \Scoop\Context::getService($serviceName);
    }
}
