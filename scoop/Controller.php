<?php

namespace Scoop;

abstract class Controller
{
    private static $request;
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

    public static function setRequest(\Scoop\Http\Request $request)
    {
        self::$request = $request;
    }

    /**
     * Realiza la redirección a la página pasada como parámetro.
     * @param string $url Dirección a la que se redirecciona la página.
     * @param integer $status Código de la redirección que se va a realizar.
     */
    public static function redirect($url, $status = 302)
    {
        header(self::$redirects[$status], true, $status);
        if (is_array($url)) {
            $config = \Scoop\Context::getEnvironment();
            $url = $config->getURL($url);
        }
        header('Location:' . $url);
        exit;
    }

    /**
     * Retorna al usuario a la página anterior.
     */
    public static function goBack()
    {
        $http_referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : self::$request->reference('http');
        if ($http_referer) {
            self::redirect($http_referer);
        }
        throw new \RuntimeException('HTTP reference losed');
    }

    final protected function getRequest()
    {
        return self::$request;
    }

    /**
     * Atajo para lanzar una excepción 404 desde el controlador.
     * @deprecated 7.1
     * @param string $msg Mensaje enviado a la excepción.
     * @throws \Scoop\Http\NotFoundException
     */
    protected function notFound($msg = 'not found')
    {
        throw new \Scoop\Http\NotFoundException($msg);
    }

    /**
     * Atajo para lanzar una excepción 403 desde el controlador.
     * @deprecated 7.1
     * @param string $msg Mensaje enviado a la excepción.
     * @throws \Scoop\Http\accessDeniedException
     */
    protected function denyAccess($msg = 'deny access')
    {
        throw new \Scoop\Http\AccessDeniedException($msg);
    }

    /**
     * Ejecuta las validaciones y si no pasan se lanza una excepción 400.
     * @param \Scoop\Validator $validator Objeto que contiene las validaciones a realizar.
     * @param array<mixed> $data contiene los datos a ser validados.
     * @throws \Scoop\Http\BadRequestException
     * @deprecated 7.2
    */
    protected function validate($validator, $data)
    {
        if ($validator->validate($data)) {
            return $validator->getData();
        }
        $errors = $validator->getErrors();
        header('HTTP/1.0 400 Bad Request');
        if (self::$request->isAjax()) {
            header('Content-Type: application/json');
            exit (json_encode(array('code' => 400, 'message' => $errors)));
        }
        $_SESSION['data-scoop'] += array(
            'body' => self::$request->getBody(),
            'query' => self::$request->getQuery(),
            'error' => $errors
        );
        $this->goBack();
    }
}
