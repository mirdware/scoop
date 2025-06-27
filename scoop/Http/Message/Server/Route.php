<?php

namespace Scoop\Http\Message\Server;

class Route
{
    private $id;
    private $variables;
    private $query;
    private $message;

    public function __construct($id)
    {
        $this->id = $id;
        $this->variables = array();
        $this->query = array();
        $this->message = array();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getVariable($name)
    {
        if (isset($this->variables[$name])) {
            return $this->variables[$name];
        }
        return null;
    }

    public function withVariables($variables)
    {
        $new = clone $this;
        $new->variables += $variables;
        return $new;
    }

    public function withQuery($query)
    {
        $new = clone $this;
        $new->query += $query;
        return $new;
    }

    public function withMessage($text, $type = 'sucess')
    {
        $new = clone $this;
        $new->message = array(
            'text' => $text,
            'type' => $type
        );
        return $new;
    }

    public function flushMessage(\Scoop\Http\Message\Server\Flash $flash)
    {
        if (!empty($this->message)) {
            $flash->set('message', $this->message);
        }
    }

    public function getURL(\Scoop\Http\Router $router, $routes)
    {
        if (!isset($routes[$this->id])) {
            throw new \InvalidArgumentException("Route '$this->id' not found");
        }
        $path = preg_split('/\[\w+\]/', $routes[$this->id]['url']);
        $url = array_shift($path);
        $count = count($path);
        if (count($this->variables) !== $count) {
            $plural = $count > 1 ? 's' : '';
            throw new \InvalidArgumentException("'$this->id' unformed URL with $count variable{$plural}");
        }
        if (array_keys($this->variables) === range(0, $count - 1)) {
            for ($i = 0; $i < $count; $i++) {
                if (isset($this->variables[$i])) {
                    $url .= self::encodeURL(trim($this->variables[$i])) . $path[$i];
                }
            }
            return rtrim(ROOT, '/') . $url . $router->formatQueryString($this->query);
        }
        $urlKeys = array_keys($this->variables);
        foreach ($urlKeys as $i => $urlKey) {
            $urlKeys[$i] = "[$urlKey]";
            if (strpos($routes[$this->id]['url'], $urlKeys[$i]) === false) {
                throw new \InvalidArgumentException("{$urlKeys[$i]} not found in URL");
            }
        }
        return rtrim(ROOT, '/') . str_replace(
            $urlKeys,
            array_values($this->variables),
            $routes[$this->id]['url']
        ) . $router->formatQueryString($this->query);
    }

    private static function encodeURL($str)
    {
        $str = str_replace(
            array('á', 'à', 'ä', 'â', 'ª', 'Á', 'À', 'Â', 'Ä'),
            'a',
            $str
        );
        $str = str_replace(
            array('é', 'è', 'ë', 'ê', 'É', 'È', 'Ê', 'Ë'),
            'e',
            $str
        );
        $str = str_replace(
            array('í', 'ì', 'ï', 'î', 'Í', 'Ì', 'Ï', 'Î'),
            'i',
            $str
        );
        $str = str_replace(
            array('ó', 'ò', 'ö', 'ô', 'Ó', 'Ò', 'Ö', 'Ô'),
            'o',
            $str
        );
        $str = str_replace(
            array('ú', 'ù', 'ü', 'û', 'Ú', 'Ù', 'Û', 'Ü'),
            'u',
            $str
        );
        $str = str_replace(
            array(' ', 'ñ', 'Ñ', 'ç', 'Ç'),
            array('-', 'n', 'N', 'c', 'C'),
            $str
        );
        return urlencode($str);
    }
}
