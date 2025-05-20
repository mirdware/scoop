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
        return ROOT . self::$assets['path'] . $resource;
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
        return $this->asset(self::$assets['css'] . $styleSheet);
    }

    /**
     * Obtiene la ruta configurada hasta el path de javascript.
     * @param string $javaScript Nombre del archivo javascript a obtener.
     * @return string ruta al archivo javascript especificada.
     */
    public function js($javaScript)
    {
        return $this->asset(self::$assets['js'] . $javaScript);
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
    public function __call($method, $args)
    {
        if (strpos($method, 'compose') !== 0) {
            trigger_error('Call to undefined method ' . __CLASS__ . '::' . $method . '()', E_USER_ERROR);
        }
        $component = lcfirst(substr($method, 7));
        if (isset($this->components[$component])) {
            $component = new \ReflectionClass($this->components[$component]);
            $component = $component->newInstanceArgs($args);
            $component = $component->render();
            return ($component instanceof \Scoop\View) ? $component->render() : $component;
        }
        throw new \BadMethodCallException('Component ' . $component . ' unregistered');
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
        return $url[0] . $this->formatQueryString($query);
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
