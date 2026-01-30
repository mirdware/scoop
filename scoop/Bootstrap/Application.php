<?php

namespace Scoop\Bootstrap;

class Application
{
    private $environment;

    public function __construct()
    {
        $this->environment = \Scoop\Context::inject('\Scoop\Bootstrap\Environment');
        $this->enableCORS();
    }

    public function run()
    {
        $requestType = $this->environment->getConfig('request', '\Scoop\Http\Message\Server\Request');
        $router = \Scoop\Context::inject('\Scoop\Http\Router');
        $request = \Scoop\Context::inject($requestType);
        try {
            $response = $router->route($request);
            gc_collect_cycles();
            return $this->printResponse($response);
        } catch (\Exception $ex) {
            $exceptionManager = \Scoop\Context::inject('\Scoop\Http\Exception\Manager');
            $dispatcher = \Scoop\Context::inject('\Scoop\Event\Dispatcher');
            \Scoop\Context::reset();
            $status = $exceptionManager->getStatusCode($ex);
            $dispatcher->dispatch(new \Scoop\Http\Event\ErrorOccurred($ex, $status));
            if (!$status) throw $ex;
            return $this->printResponse($exceptionManager->handle(
                $ex,
                $request->isAjax(),
                $status
            ));
        }
    }

    private function printResponse($response)
    {
        $ignore = array(
            'transfer-encoding' => 1,
            'content-encoding' => 1,
            'connection' => 1,
            'keep-alive' => 1,
            'proxy-authenticate' => 1,
            'proxy-authorization' => 1,
            'te' => 1,
            'trailers' => 1,
            'upgrade' => 1
        );
        http_response_code($response->getStatusCode());
        $headers = $response->getHeaders();
        foreach ($headers as $name => $values) {
            if (!isset($ignore[strtolower($name)])) {
                foreach ($values as $value) {
                    header("$name: $value", false);
                }
            }
        }
        $body = $response->getBody();
        $body->rewind();
        $resource = $body->detach();
        fpassthru($resource);
        fclose($resource);
    }

    private function enableCORS()
    {
        $cors = $this->environment->getConfig('cors');
        if (!$cors) {
            return;
        }
        if (isset($_SERVER['HTTP_ORIGIN'])) {
            $origin = isset($cors['origin']) ?
            array_map('trim', explode(',', $cors['origin'])) :
            array($_SERVER['HTTP_ORIGIN']);
            if (in_array($_SERVER['HTTP_ORIGIN'], $origin)) {
                header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
                header('Access-Control-Allow-Credentials: true');
                header('Access-Control-Max-Age: 86400');
            }
        }
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])) {
                $methods = isset($cors['methods']) ?
                $cors['methods'] :
                $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'];
                header("Access-Control-Allow-Methods: $methods");
            }
            if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
                $headers = isset($cors['headers']) ?
                $cors['headers'] :
                $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'];
                header("Access-Control-Allow-Headers: $headers");
            }
            exit;
        }
    }
}
