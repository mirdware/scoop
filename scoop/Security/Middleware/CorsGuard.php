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
        $this->addOriginHeader($serverParams);
        if (strtoupper($request->getMethod()) === 'OPTIONS') {
            return $this->getPreflightResponse($serverParams);
        }
        return $next->handle($request);
    }

    private function getPreflightResponse($serverParams)
    {
        $response = new \Scoop\Http\Message\Response();
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

    private function addOriginHeader($serverParams)
    {
        if (!isset($serverParams['HTTP_ORIGIN'])) {
            return;
        }
        $allowedOrigins = isset($this->config['origins']) ?
        array_map('trim', explode(',', $this->config['origins'])) :
        array($serverParams['HTTP_ORIGIN']);
        if (!$this->isOriginAllowed($serverParams['HTTP_ORIGIN'], $allowedOrigins)) {
            return;
        }
        if (!empty($this->config['expose-headers'])) {
            header("Access-Control-Expose-Headers: {$this->config['expose-headers']}");
        }
        $maxAge = isset($this->config['max-age']) ? $this->config['max-age'] :  86400;
        if (!isset($this->config['credentials']) || $this->config['credentials']) {
            header('Access-Control-Allow-Credentials: true');
        }
        header("Access-Control-Allow-Origin: {$serverParams['HTTP_ORIGIN']}");
        header('Vary: Origin');
        header("Access-Control-Max-Age: $maxAge");
    }

    private function isOriginAllowed($requestOrigin, $allowedOrigins)
    {
        foreach ($allowedOrigins as $allowed) {
            if ($requestOrigin === $allowed) {
                return true;
            }
            if (strpos($allowed, '*') !== false) {
                $pattern = str_replace('\\*', '[^.]+', preg_quote($allowed, '#'));
                if (preg_match('#^' . $pattern . '$#i', $requestOrigin)) {
                    return true;
                }
            }
        }
        return false;
    }
}
