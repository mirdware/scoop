<?php

namespace Scoop\Security\Middleware;

class CorsGuard
{
    private $config;

    public function __construct($config = array())
    {
        $this->config = $config;
    }
    public function process($request, $next)
    {
        $serverParams = $request->getServerParams();
        if ($request->getMethod() === 'options') {
            return $this->getPreflightResponse($serverParams);
        }
        return $this->addOriginHeader($next->handle($request), $serverParams);
    }

    private function getPreflightResponse($serverParams)
    {
        $response = $this->addOriginHeader(
            new \Scoop\Http\Message\Response(),
            $serverParams
        );
        if (isset($serverParams['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])) {
            $response = $response->withHeader(
                'Access-Control-Allow-Methods',
                isset($this->config['methods']) ?
                $this->config['methods'] :
                $serverParams['HTTP_ACCESS_CONTROL_REQUEST_METHOD']
            );
        }
        if (isset($serverParams['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
            $response = $response->withHeader(
                'Access-Control-Allow-Headers',
                isset($this->config['headers']) ?
                $this->config['headers'] :
                $serverParams['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']
            );
        }
        return $response;
    }

    private function addOriginHeader($response, $serverParams)
    {
        if (!isset($serverParams['HTTP_ORIGIN'])) {
            return $response;
        }
        $allowedOrigins = isset($this->config['origin']) ?
        array_map('trim', explode(',', $this->config['origin'])) :
        array($serverParams['HTTP_ORIGIN']);
        if (!$this->isOriginAllowed($serverParams['HTTP_ORIGIN'], $allowedOrigins)) {
            return $response;
        }
        if (!empty($this->config['expose-headers'])) {
            $response = $response->withHeader('Access-Control-Expose-Headers', $this->config['expose-headers']);
        }
        $credentials = isset($this->config['credentials']) ? $this->config['credentials'] : true;
        $maxAge = isset($this->config['max-age']) ? $this->config['max-age'] :  86400;
        return $response
        ->withHeader('Access-Control-Allow-Origin', $serverParams['HTTP_ORIGIN'])
        ->withHeader('Vary', 'Origin')
        ->withHeader('Access-Control-Allow-Credentials', $credentials)
        ->withHeader('Access-Control-Max-Age', $maxAge);
    }

    private function isOriginAllowed($requestOrigin, $allowedOrigins)
    {
        foreach ($allowedOrigins as $allowed) {
            if ($requestOrigin === $allowed) {
                return true;
            }
            if (strpos($allowed, '*') !== false) {
                $pattern = str_replace(
                    array('\\*', '.', '/'),
                    array('[^.]+', '\\.', '\\/'),
                    preg_quote($allowed, '/')
                );
                if (preg_match('/^' . $pattern . '$/', $requestOrigin)) {
                    return true;
                }
            }
        }
        return false;
    }
}
