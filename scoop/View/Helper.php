<?php

namespace Scoop\View;

class Helper
{
    private $components;
    private $environment;
    private $request;
    private $router;
    private $viteHost;
    private $data;
    private $heritage;
    private static $keyMessages = 'messages.';
    private static $assets = array(
        'path' => 'public/',
        'img' => 'images/',
        'css' => 'css/',
        'js' => 'js/'
    );

    public function __construct(
        \Scoop\Http\Message\Server\Request $request,
        \Scoop\Bootstrap\Environment $environment,
        \Scoop\Http\Router $router,
        \Scoop\View\Heritage $heritage,
        $data
    ) {
        $this->environment = $environment;
        $this->heritage = $heritage;
        $this->request = $request;
        $this->router = $router;
        $this->data = $data;
        $this->components = array(
            'message' => '\Scoop\View\Message'
        ) + $environment->getConfig('components', array());
        self::$assets = $environment->getConfig('assets', array()) + self::$assets;
        $this->viteHost = getenv('VITE_HOST');
    }

    public function asset($resource)
    {
        return ROOT . self::$assets['path'] . $resource;
    }

    public function img($image)
    {
        return $this->asset(self::$assets['img'] . $image);
    }

    public function css($styleSheet)
    {
        if ($this->viteHost) {
            return "{$this->viteHost}app/styles/app.styl";
        }
        return $this->asset(self::$assets['css'] . $styleSheet) . '?v=' . $this->environment->getConfig('app.version');
    }

    public function js($javaScript)
    {
        if ($this->viteHost) {
            return "{$this->viteHost}app/scripts/app.js";
        }
        return $this->asset(self::$assets['js'] . $javaScript) . '?v=' . $this->environment->getConfig('app.version');
    }

    public function route()
    {
        $args = func_get_args();
        $query = array();
        if (!empty($args) && is_array(end($args))) {
            $query = array_pop($args);
        }
        if (!empty($args)) {
            $route = new \Scoop\Http\Message\Server\Route(array_shift($args));
            return $this->router->getURL($route
                ->withParameters($args)
                ->withQuery($query)
            );
        }
        $host = $this->viteHost ? rtrim($this->viteHost, '/') : '//' . $_SERVER['HTTP_HOST'];
        $query = array_merge($this->request->getQueryParams(), $query);
        $queryString = http_build_query($query);
        if ($queryString) {
            $queryString = "?$queryString";
        }
        return $host . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) . $queryString;
    }

    public function getRoutePath($id) {
        $path = $this->router->getPath($id);
        if (!$path) {
            throw new \InvalidArgumentException("route with id $id not found");
        }
        return rtrim(ROOT, '/') . $path;
    }

    public function addPage($data, $quantity, $name = 'page')
    {
        $query = $this->request->getQueryParams();
        $nextPage = $data['page'] + $quantity;
        if ($nextPage < 0 || $nextPage * $data['size'] >= $data['total']) {
            return $this->route();
        }
        $query[$name] = $nextPage;
        return $this->route($query);
    }

    public function getConfig($name, $default = '')
    {
        return $this->environment->getConfig($name, $default);
    }

    public function translate($msg)
    {
        return $this->environment->getConfig(self::$keyMessages . $msg);
    }

    public function getCsrfToken()
    {
        if (!isset($_SESSION['csrf-token'])) {
            $_SESSION['csrf-token'] = $this->generateToken();
        }
        return $_SESSION['csrf-token'];
    }

    public function isCurrentRoute($routeId)
    {
        $route = $this->router->getCurrentRoute();
        return $route->getId() === $routeId;
    }

    public function fetch($name)
    {
        return $this->request->flash()->get($name);
    }

    public function compose($name, $props, $children)
    {
        if (strpos($name, 'view.') === 0) {
            $viewName = str_replace('.', '/', substr($name, 5));
            $view = new \Scoop\View($viewName);
            $view->add($props);
            return Heritage::parseBlocks($children, $view->render());
        }
        if (!isset($this->components[$name])) {
            throw new \UnexpectedValueException("Error building the component [component $name not found].");
        }
        $component = \Scoop\Context::inject($this->components[$name]);
        $component = $component->render($props, $this->data);
        $subject = ($component instanceof \Scoop\View) ? $component->render() : Template::clearHTML($component);
        return Heritage::parseBlocks($children, $subject);
    }

    public function getCompilePath($path)
    {
        return $this->heritage->getCompilePath($path);
    }

    public function setParent()
    {
        $this->heritage->setParent();
    }

    public function escape($string)
    {
        if (is_string($string)) {
            return htmlspecialchars($string, ENT_QUOTES);
        }
        return $string;
    }

    public static function setKeyMessages($key)
    {
        self::$keyMessages = $key;
    }

    private function generateToken()
    {
        if (function_exists('openssl_random_pseudo_bytes')) {
            $bytes = openssl_random_pseudo_bytes(32, $strong);
            if ($strong === true) {
                return bin2hex($bytes);
            }
        }
        $sources = array(
            uniqid('', true),
            mt_rand(),
            microtime(true),
            isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '',
            isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '',
            php_uname(),
            getmypid(),
            memory_get_usage()
        );
        $token = implode('|', $sources);
        for ($i = 0; $i < 100; $i++) {
            $token = hash('sha256', $token . mt_rand());
        }
        return $token;
    }
}
