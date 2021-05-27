<?php
namespace Scoop\Bootstrap;

class Application
{
    private $url;
    private $environment;

    public function __construct(Environment $environment)
    {
        $this->environment = $environment;
        $this->enableCORS();
    }

    public function run()
    {
        $url = $this->getURL();
        $response = $this->environment->route($url);
        return $this->formatResponse($response);
    }

    public function showError($error)
    {
        try {
            return $this->formatResponse($error);
        } catch (\UnderflowException $ex) {}
    }

    public function setURL($url)
    {
        $this->url = $url;
        return $this;
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

    private function getURL()
    {
        if (!isset($this->url)) {
            if (substr($_SERVER['REQUEST_URI'], -9) === 'index.php') {
                \Scoop\Controller::redirect(
                    str_replace('index.php', '', $_SERVER['REQUEST_URI']), 301
                );
            }
            $this->url = '/';
            if (isset($_GET['route'])) {
                $this->url .= filter_var($_GET['route'], FILTER_SANITIZE_URL);
                unset($_GET['route'], $_REQUEST['route']);
            }
        }
        return $this->url;
    }

    private function enableCORS()
    {
        $cors = $this->environment->get('cors');
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
