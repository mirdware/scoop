<?php
namespace Scoop\Bootstrap;

class Application
{
    private $router;
    private $url;
    private $environment;

    public function __construct(Environment $environment)
    {
        $this->environment = $environment;
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

    private function formatResponse($response)
    {
        if ($response === null) return header('HTTP/1.0 204 No Response');
        if ($response instanceof \Scoop\View) {
            header('Content-Type:text/html');
            return $response->render();
        }
        if (is_array($response)) {
            header('Content-Type:application/json');
            return json_encode($response);
        }
        return $response;
    }

    public function setURL($url)
    {
        $this->url = $url;
        return $this;
    }

    private function getURL()
    {
        if (!isset($this->url)) {
            if (substr($_SERVER['REQUEST_URI'], -9) === 'index.php') {
                \Scoop\Controller::redirect(
                    str_replace('index.php', '', $_SERVER['REQUEST_URI']), 301
                );
            }
            $this->url = '/'.filter_input(INPUT_GET, 'route', FILTER_SANITIZE_STRING);
            unset($_GET['route'], $_REQUEST['route']);
        }
        return $this->url;
    }
}
