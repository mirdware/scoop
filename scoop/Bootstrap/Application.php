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
        if (substr($_SERVER['REQUEST_URI'], -9) === 'index.php') {
            \Scoop\Controller::redirect(
                str_replace('index.php', '', $_SERVER['REQUEST_URI']), 301
            );
        }
        exit($this->invoke());
    }

    public function invoke()
    {
        $url = $this->getURL();
        $router = $this->environment->getRouter();
        $response = $router->route($url);
        if ($response === null) {
            header('HTTP/1.0 204 No Response');
        } elseif ($response instanceof \Scoop\View) {
            $response = $response->render();
        } elseif (is_array($response)) {
            header('Content-Type: application/json');
            $response = json_encode($response);
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
            $this->url = '/'.filter_input(INPUT_GET, 'route', FILTER_SANITIZE_STRING);
            unset($_GET['route'], $_REQUEST['route']);
        }
        return $this->url;
    }
}
