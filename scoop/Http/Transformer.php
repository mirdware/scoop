<?php

namespace Scoop\Http;

class Transformer
{
    public function transformMissingParameterException(\InvalidArgumentException $ex) {
        return new \Scoop\Http\Exception\NotFound('Page or resource not found [missing parameter]', $ex);
    }

    public function transformMissingMethodException(\BadMethodCallException $ex, $method) {
        return new \Scoop\Http\Exception\MethodNotAllowed("Resource does not support $method method", $method, $ex);
    }

    public function transformResponse($response)
    {
        if ($response instanceof \Scoop\Http\Message\Response) {
            return $response;
        }
        if ($response === null || $response === '') {
            return new \Scoop\Http\Message\Response();
        }
        $headers = array('Content-Type' => 'application/json');
        if ($response instanceof \Scoop\View) {
            $headers['Content-Type'] = 'text/html';
            return new \Scoop\Http\Message\Response(200, $headers, $response->render());
        }
        if (is_scalar($response) || is_object($response) && method_exists($response, '__toString')) {
            $headers['Content-Type'] = 'text/plain';
            return new \Scoop\Http\Message\Response(200, $headers, $response);
        }
        return new \Scoop\Http\Message\Response(200, $headers, json_encode($response));
    }
}
