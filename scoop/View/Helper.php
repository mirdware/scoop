<?php

namespace Scoop\View;

class Helper
{
    private $components;
    private $environment;
    private $request;
    private $router;
    private static $keyMessages = 'messages.';
    private static $assets = array(
        'path' => 'public/',
        'img' => 'images/',
        'css' => 'css/',
        'js' => 'js/'
    );

    public function __construct($request, $components)
    {
        $this->request = $request;
        $this->components = $components;
        $this->environment = \Scoop\Context::inject('\Scoop\Bootstrap\Environment');
        $this->router = \Scoop\Context::inject('\Scoop\Http\Router');
        self::$assets = $this->environment->getConfig('assets', array()) + self::$assets;
    }

    /**
     * Obtiene la ruta configurada hasta el path publico.
     * @param string $resource Nombre del recurso a obtener.
     * @return string ruta al recurso especificado.
     */
    public function asset($resource)
    {
        $host = getenv('VITE_ENVIRONMENT');
        return ($host ? "$host/" : ROOT) . self::$assets['path'] . $resource;
    }

    /**
     * Obtiene la ruta configurada hasta el path de imagenes.
     * @param string $image Nombre de la imagen a obtener.
     * @return string ruta a la imagen especificada.
     */
    public function img($image)
    {
        return $this->asset(self::$assets['img'] . $image);
    }

    /**
     * Obtiene la ruta configurada hasta el path de hojas de estilos.
     * @param string $styleSheet Nombre del archivo css a obtener.
     * @return string ruta a la hoja de estilos especificada.
     */
    public function css($styleSheet)
    {
        $host = getenv('VITE_ENVIRONMENT');
        if ($host) {
            return "$host/app/styles/app.styl";
        }
        return $this->asset(self::$assets['css'] . $styleSheet) . '?v=' . $this->environment->getConfig('app.version');
    }

    /**
     * Obtiene la ruta configurada hasta el path de javascript.
     * @param string $javaScript Nombre del archivo javascript a obtener.
     * @return string ruta al archivo javascript especificada.
     */
    public function js($javaScript)
    {
        $host = getenv('VITE_ENVIRONMENT');
        if ($host) {
            return "$host/app/scripts/app.js";
        }
        return $this->asset(self::$assets['js'] . $javaScript) . '?v=' . $this->environment->getConfig('app.version');
    }

    /**
     * Obtiene la URL formateada según la ruta y parametros enviados.
     * @param mixed $args Recibe como parametros ($nombreRuta, $params...).
     * @return string URL formateada según el nombre de la ruta y los parámetros
     * enviados.
     */
    public function route()
    {
        $args = func_get_args();
        $query = array_pop($args);
        if ($query !== null && !is_array($query)) {
            array_push($args, $query);
            $query = array();
        }
        if (empty($args)) {
            $currentPath = '//' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            return $this->mergeQuery($currentPath, $query);
        }
        return $this->router->getURL(array_shift($args), $args, $query);
    }

    public function addPage($data, $quantity, $name = 'page')
    {
        $queryString = $this->request->getQuery();
        $nextPage = $data['page'] + $quantity;
        if ($nextPage < 0 || $nextPage * $data['size'] >= $data['total']) {
            return $this->route();
        }
        $queryString[$name] = $nextPage;
        return $this->route($queryString);
    }

    public function getConfig($name, $default = '')
    {
        return $this->environment->getConfig($name, $default);
    }

    public function translate($msg)
    {
        return $this->environment->getConfig(self::$keyMessages . $msg);
    }

    public function isCurrentRoute($routeKey)
    {
        $route = $this->router->getCurrentRoute();
        return $route['key'] === $routeKey;
    }

    public function fetch($name)
    {
        return $this->request->reference($name);
    }

    /**
     * Compone la clase dependiendo de los parametros dados.
     * @return string Estructura HTML del componente generado.
     */
    public function compose($name, $props, $children)
    {

        if (!isset($this->components[$name])) {
            throw new \UnexpectedValueException("Error building the component [component $name not found].");
        }
        $component = \Scoop\Context::inject($this->components[$name]);
        $props['children'] = $children;
        $component = $component->render($props);
        return ($component instanceof \Scoop\View) ? $component->render() : $component;
    }

    public static function setKeyMessages($key)
    {
        self::$keyMessages = $key;
    }

    private function mergeQuery($url, $query)
    {
        $url = explode('?', $url);
        if (isset($url[1])) {
            $query += $this->getQuery($url[1]);
        }
        return $url[0] . $this->router->formatQueryString($query);
    }

    private function getQuery($params)
    {
        $query = array();
        $params = explode('&', $params);
        foreach ($params as $param) {
            $param = explode('=', $param);
            $query[$param[0]] = $param[1];
        }
        return $query;
    }
}
