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
        try {
            return $this->addOriginHeader($next->handle($request), $serverParams);
        } catch (\Exception $ex) {
            $this->addErrorHeaders($serverParams);
            throw $ex;
        } catch (\Throwable $ex) {
            $this->addErrorHeaders($serverParams);
            throw $ex;
        }
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

    private function addErrorHeaders($serverParams)
    {
        $response = $this->addOriginHeader(
            new \Scoop\Http\Message\Response(),
            $serverParams
        );
        $headers = $response->getHeaders();
        foreach ($headers as $name => $values) {
            foreach ($values as $value) {
                header("$name: $value", false);
            }
        }
    }

    private function addOriginHeader($response, $serverParams)
    {
        if (!isset($serverParams['HTTP_ORIGIN'])) {
            return $response;
        }
        $allowedOrigins = isset($this->config['origins']) ?
        array_map('trim', explode(',', $this->config['origins'])) :
        array($serverParams['HTTP_ORIGIN']);
        if (!$this->isOriginAllowed($serverParams['HTTP_ORIGIN'], $allowedOrigins)) {
            return $response;
        }
        if (!empty($this->config['expose-headers'])) {
            $response = $response->withHeader('Access-Control-Expose-Headers', $this->config['expose-headers']);
        }
        $maxAge = isset($this->config['max-age']) ? $this->config['max-age'] :  86400;
        if (!isset($this->config['credentials']) || boolval($this->config['credentials'])) {
            $response = $response->withHeader('Access-Control-Allow-Credentials', 'true');
        }
        return $response
        ->withHeader('Access-Control-Allow-Origin', $serverParams['HTTP_ORIGIN'])
        ->withHeader('Vary', 'Origin')
        ->withHeader('Access-Control-Max-Age', $maxAge);
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
