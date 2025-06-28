<?php

namespace Scoop\Http\Message\Server;

class Route
{
    private $id;
    private $parameters;
    private $query;
    private $message;

    public function __construct($id)
    {
        $this->id = $id;
        $this->parameters = array();
        $this->query = array();
        $this->message = array();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getParameter($name)
    {
        if (isset($this->parameters[$name])) {
            return $this->parameters[$name];
        }
        return null;
    }

    public function withParameters()
    {
        $new = clone $this;
        $args = func_get_args();
        if (is_array($args[0])) {
            $new->parameters += $args[0];
            return $new;
        }
        $new->parameters += $args;
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

    public function getURL($routes)
    {
        if (!isset($routes[$this->id])) {
            throw new \InvalidArgumentException("Route '$this->id' not found");
        }
        $path = preg_split('/\[\w+\]/', $routes[$this->id]['url']);
        $url = array_shift($path);
        $count = count($path);
        if (count($this->parameters) !== $count) {
            throw new \InvalidArgumentException("$this->id unformed URL {$routes[$this->id]['url']}");
        }
        $queryString = http_build_query($this->query);
        if ($queryString) {
            $queryString = "?$queryString";
        }
        if (array_keys($this->parameters) === range(0, $count - 1)) {
            for ($i = 0; $i < $count; $i++) {
                $slug = self::transliterate($$this->parameters[$i]);
                $url .= self::normalizeURL($slug) . $path[$i];
            }
            return rtrim(ROOT, '/') . $url . $queryString;
        }
        $urlKeys = array_keys($this->parameters);
        $urlValues = array_values($this->parameters);
        foreach ($urlKeys as $i => $urlKey) {
            $urlKeys[$i] = "[$urlKey]";
            if (strpos($routes[$this->id]['url'], $urlKeys[$i]) === false) {
                throw new \InvalidArgumentException("{$urlKeys[$i]} not found in URL");
            }
            $slug = self::transliterate($urlValues[$i]);
            $urlValues[$i] = self::normalizeURL($slug);
        }
        return rtrim(ROOT, '/') . str_replace(
            $urlKeys,
            $urlValues,
            $routes[$this->id]['url']
        ) . $queryString;
    }

    private static function transliterate($str)
    {
        if (class_exists('Transliterator')) {
            $transliterator = \Transliterator::create('Any-Latin; Latin-ASCII;');
            return $transliterator->transliterate($str);
        }
        if (function_exists('iconv')) {
            $originalLocale = setlocale(LC_ALL, 0);
            setlocale(LC_ALL, 'en_US.UTF-8');
            $transliterated = iconv('UTF-8', 'ASCII//TRANSLIT', $str);
            setlocale(LC_ALL, $originalLocale);
            if ($transliterated !== false) {
                return $transliterated;
            }
        }
        return str_replace(
            array(
                'á', 'à', 'ä', 'â', 'ª',  'Á', 'À', 'Ä', 'Â',
                'é', 'è', 'ë', 'ê', 'É', 'È', 'Ë', 'Ê',
                'í', 'ì', 'ï', 'î', 'Í', 'Ì', 'Ï', 'Î',
                'ó', 'ò', 'ö', 'ô', 'Ó', 'Ò', 'Ö', 'Ô',
                'ú', 'ù', 'ü', 'û', 'Ú', 'Ù', 'Ü', 'Û',
                'ñ', 'Ñ', 'ç', 'Ç',' '
            ), array(
                'a', 'a', 'a', 'a', 'a', 'A', 'A', 'A', 'A',
                'e', 'e', 'e', 'e', 'E', 'E', 'E', 'E',
                'i', 'i', 'i', 'i', 'I', 'I', 'I', 'I',
                'o', 'o', 'o', 'o', 'O', 'O', 'O', 'O',
                'u', 'u', 'u', 'u', 'U', 'U', 'U', 'U',
                'n', 'N', 'c', 'C', '-'
            ),
            $str
        );
    }

    private static function normalizeURL($str)
    {
        $str = preg_replace('/(?<![ -])([A-Z])/', ' $1', $str);
        $str = strtolower($str);
        $str = preg_replace('/[^a-z0-9]+/', '-', $str);
        $str = trim($str, '-');
        $str = preg_replace('/-+/', '-', $str);
        return empty($str) ? 'void' : $str;
    }
}
