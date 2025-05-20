<?php

namespace Scoop\Http\Message\Server;

use Scoop\Http\Message\URI;
use Scoop\View\Message;

class Request extends \Scoop\Http\Message\Request
{
    private $serverParams;
    private $cookieParams;
    private $queryParams;
    private $body;
    private $parsedBody;
    private $attributes;
    private $referencer;
    private $urlPath;
    private static $redirects = array(
        300 => 'HTTP/1.1 300 Multiple Choices',
        301 => 'HTTP/1.1 301 Moved Permanently',
        302 => 'HTTP/1.1 302 Found',
        303 => 'HTTP/1.1 303 See Other',
        304 => 'HTTP/1.1 304 Not Modified',
        305 => 'HTTP/1.1 305 Use Proxy',
        306 => 'HTTP/1.1 306 Not Used',
        307 => 'HTTP/1.1 307 Temporary Redirect',
        308 => 'HTTP/1.1 308 Permanent Redirect'
    );

    public function __construct(
        \Scoop\Http\Message\URI $uri = null,
        \Scoop\Http\Message\Stream $body = null,
        $method = null,
        $headers = null,
        $queryParams = null,
        $referencer = null,
        $serverParams = null,
        $cookies = null
    ) {
        $this->urlPath = $uri ? $uri->getPath() : $this->getURLPath();
        parent::__construct(
            $uri ? $uri : new \Scoop\Http\Message\URI(
                trim(ROOT, '/') . $this->urlPath . (isset($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '')
            ),
            $method === null ? strtolower($_SERVER['REQUEST_METHOD']) : strtolower($method),
            $headers === null ? $this->getAllHeaders() : $headers,
            $body === null ? new \Scoop\Http\Message\Stream(fopen('php://input', 'r')) : $body
        );
        $this->serverParams = $serverParams === null ? $_SERVER : $serverParams;
        $this->cookieParams = $cookies === null ? $_COOKIE : $cookies;
        $this->queryParams = $queryParams === null ? $this->purge($_GET) : $queryParams;
        $this->referencer = $referencer === null ? $this->getReferencer() : $referencer;
        $this->attributes = array();
    }

    public function getServerParams()
    {
        return $this->serverParams;
    }

    public function getCookieParams()
    {
        return $this->cookieParams;
    }

    public function getPath()
    {
        return $this->urlPath;
    }

    public function withCookieParams($cookies)
    {
        $new = clone $this;
        $new->cookieParams = $cookies;
        return $new;
    }

    public function getQueryParams()
    {
        return $this->queryParams;
    }

    public function withQueryParams($query)
    {
        $new = clone $this;
        $new->queryParams = $query;
        return $new;
    }

    public function getUploadedFiles()
    {
        if (!$this->body) {
            $this->body = new \Scoop\Http\Message\Parser\Body(
                $this->getBody()->getContents(),
                $this->getMethod(),
                $this->getHeaderLine('Content-Type')
            );
        }
        return $this->body->getFiles();
    }

    public function withUploadedFiles($uploadedFiles)
    {
        $new = clone $this;
        $new->uploadedFiles = $uploadedFiles;
        return $new;
    }

    public function getParsedBody()
    {
        if (!$this->body) {
            $this->body = new \Scoop\Http\Message\Parser\Body(
                $this->getBody()->getContents(),
                $this->getMethod(),
                $this->getHeaderLine('Content-Type')
            );
            $this->parsedBody = $this->purge($this->body->getData());
        }
        return $this->parsedBody;
    }

    public function withParsedBody($data)
    {
        $new = clone $this;
        $new->parsedBody = $data;
        return $new;
    }

    public function getAttributes()
    {
        return $this->attributes;
    }

    public function getAttribute($name, $default = null)
    {
        return isset($this->attributes[$name]) ? $this->attributes[$name] : $default;
    }

    public function withAttribute($name, $value)
    {
        $new = clone $this;
        $new->attributes[$name] = $value;
        return $new;
    }

    public function withoutAttribute($name)
    {
        $new = clone $this;
        unset($new->attributes[$name]);
        return $new;
    }

    public function get($type = null)
    {
        if ($type !== null && !class_exists($type)) {
            throw new \InvalidArgumentException("The type $type not exist");
        }
        return new Payload($this, $type);
    }

    public function redirect($url, $status = 302)
    {
        header(self::$redirects[$status], true, $status);
        if (is_array($url)) {
            $router = \Scoop\Context::inject('\Scoop\Http\Router');
            $url = $router->getURL($url);
        }
        header('Location:' . $url);
        exit;
    }

    public function goBack()
    {
        $http_referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $this->reference('http');
        if ($http_referer) {
            $this->redirect($http_referer);
        }
    }

    public function reference($name)
    {
        $name = explode('.', $name);
        $ref = $this->referencer;
        foreach ($name as $key) {
            if (!isset($ref[$key])) {
                return '';
            }
            $ref = $ref[$key];
        }
        return $ref;
    }

    private function getURLPath()
    {
        $url = '/';
        if (isset($_GET['route'])) {
            $url .= filter_var($_GET['route'], FILTER_SANITIZE_URL);
            unset($_GET['route'], $_REQUEST['route']);
        }
        return $url;
    }

    private function getAllHeaders()
    {
        $headers = array();
        foreach ($_SERVER as $name => $value) {
            if (strpos($name, 'HTTP_') === 0) {
                $headerName = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
                $headers[$headerName] = array_map('trim', explode(',', $value));
            } elseif (in_array($name, array('CONTENT_TYPE', 'CONTENT_LENGTH', 'CONTENT_MD5'), true)) {
                $headerName = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', $name))));
                $headers[$headerName] = array_map('trim', explode(',', $value));
            }
        }
        return $headers;
    }

    private function getReferencer()
    {
        $referencer = isset($_SESSION['data-scoop']) ? $_SESSION['data-scoop'] : array();
        $contentType = $this->getHeaderLine('Content-Type');
        if (strpos($contentType, 'application/json') === false) {
            $_SESSION['data-scoop'] = array(
                'http' => substr(ROOT, 0, strpos(ROOT, '/', 7)) . $_SERVER['REQUEST_URI']
            );
        }
        return $referencer;
    }

    private function purge($array)
    {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $array[$key] = $this->purge($value);
            } elseif (is_string($value)) {
                $array[$key] = $this->filterXSS($value);
            }
        }
        return $array;
    }

    private static function filterXSS($data)
    {
        $data = str_replace(array('&amp;','&lt;','&gt;'), array('&amp;amp;','&amp;lt;','&amp;gt;'), $data);
        $data = preg_replace('/(&#*\w+)[\x00-\x20]+;/u', '$1;', $data);
        $data = preg_replace('/(&#x*[0-9A-F]+);*/iu', '$1;', $data);
        $data = html_entity_decode($data, ENT_COMPAT, 'UTF-8');
        $data = preg_replace('#(<[^>]+?[\x00-\x20"\'])(?:on|xmlns)[^>]*+>#iu', '$1>', $data);
        $data = preg_replace('#([a-z]*)[\x00-\x20]*=[\x00-\x20]*([`\'"]*)[\x00-\x20]*j[\x00-\x20]*a[\x00-\x20]*v[\x00-\x20]*a[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2nojavascript...', $data);
        $data = preg_replace('#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*v[\x00-\x20]*b[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2novbscript...', $data);
        $data = preg_replace('#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*-moz-binding[\x00-\x20]*:#u', '$1=$2nomozbinding...', $data);
        $data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?expression[\x00-\x20]*\([^>]*+>#i', '$1>', $data);
        $data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?behaviour[\x00-\x20]*\([^>]*+>#i', '$1>', $data);
        $data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:*[^>]*+>#iu', '$1>', $data);
        $data = preg_replace('#</*\w+:\w[^>]*+>#i', '', $data);
        do {
            $old_data = $data;
            $data = preg_replace('#</*(?:applet|b(?:ase|gsound|link)|embed|frame(?:set)?|i(?:frame|layer)|l(?:ayer|ink)|meta|object|s(?:cript|tyle)|title|xml)[^>]*+>#i', '', $data);
        } while ($old_data !== $data);
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }
}
