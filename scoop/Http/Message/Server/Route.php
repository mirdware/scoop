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
        if (empty($query)) {
            return $this;
        }
        $new = clone $this;
        $new->query += $query;
        return $new;
    }

    public function withMessage($text, $type = 'success')
    {
        if ($text === $this->message['text'] && $type === $this->message['type']) {
            return $this;
        }
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

    public function generateURL($routes)
    {
        if (!isset($routes[$this->id])) {
            throw new \InvalidArgumentException("route '{$this->id}' not found");
        }
        $url = $routes[$this->id]['url'];
        preg_match_all('/\[(\w+)\]/', $url, $matches);
        $placeholders = isset($matches[1]) ? $matches[1] : array();
        $placeholdersQuantity = count($placeholders);
        $parametersQuantity = count($this->parameters);
        if ($parametersQuantity !== $placeholdersQuantity) {
            throw new \InvalidArgumentException(
                "route '{$this->id}' expects $placeholdersQuantity parameters, but $parametersQuantity were given"
            );
        }
        $keys = array_keys($this->parameters);
        $path = empty($this->parameters) || $keys === range(0, $parametersQuantity - 1) ?
        $this->buildFromPositional($url) :
        $this->buildFromNamed($url, $placeholders, $keys);
        $queryString = http_build_query($this->query);
        if ($queryString) {
            $queryString = "?$queryString";
        }
        return rtrim(ROOT, '/') . $path . $queryString;
    }

    private function buildFromPositional($url)
    {
        $i = 0;
        return preg_replace_callback('/\[\w+\]/', function($matches) use (&$i) {
            if (isset($this->parameters[$i])) {
                return self::slugify($this->parameters[$i++]);
            }
            return $matches[0];
        }, $url);
    }

    private function buildFromNamed($url, $placeholders, $keys)
    {
        $missingParams = array_diff($placeholders, $keys);
        if (!empty($missingParams)) {
            throw new \InvalidArgumentException("Missing parameters for route '{$this->id}': " . implode(', ', $missingParams));
        }
        $extraParams = array_diff($keys, $placeholders);
         if (!empty($extraParams)) {
             throw new \InvalidArgumentException("Unknown parameters for route '{$this->id}': " . implode(', ', $extraParams));
        }
        $search = array();
        $replace = array();
        foreach ($this->parameters as $key => $value) {
            $search[] = "[$key]";
            $replace[] = self::slugify($value);
        }
        return str_replace($search, $replace, $url);
    }

    private static function slugify($str)
    {
        $str = self::transliterate($str);
        return self::normalizeURL($str);
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
                'á', 'à', 'ä', 'â', 'ª', 'Á', 'À', 'Ä', 'Â',
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
