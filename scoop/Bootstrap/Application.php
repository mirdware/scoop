<?php
namespace Scoop\Bootstrap;

class Application
{
    private $request;

    public function __construct()
    {
        $this->request = new \Scoop\Http\Request();
        $this->enableCORS();
    }

    public function run()
    {
        $response = \Scoop\Context::getEnvironment()->route($this->request);
        return $this->formatResponse($response);
    }

    public function showError($ex)
    {
        try {
            if ($this->request->isAjax()) {
                $ex->addHeader('Content-Type: application/json');
            }
            return $this->formatResponse($ex->handle());
        } catch (\UnderflowException $ex) {}
    }

    private function formatResponse($response)
    {
        if ($response === null) return header('HTTP/1.0 204 No Response');
        if ($response instanceof \Scoop\View) {
            header('Content-Type:text/html');
            return $response->render();
        }
        if (is_array($response) || is_object($response)) {
            header('Content-Type:application/json');
            return json_encode($response);
        }
        return $response;
    }

    private function enableCORS()
    {
        $cors = \Scoop\Context::getEnvironment()->getConfig('cors');
        if (!$cors) return;
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
