<?php

namespace Scoop\Http\Message\Server;

class Request extends \Scoop\Http\Message\Request
{
    private $serverParams;
    private $cookieParams;
    private $queryParams;
    private $body;
    private $parsedBody;
    private $attributes;
    private $flash;
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
        $uri = null,
        $body = null,
        $method = null,
        $headers = null,
        $queryParams = null,
        $referencer = null,
        $serverParams = null,
        $cookies = null
    ) {
        $this->urlPath = $uri === null ? $this->getURLPath() : $uri->getPath();
        parent::__construct(
            $uri === null ? new \Scoop\Http\Message\URI(
                trim(ROOT, '/') . $this->urlPath . (isset($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '')
            ) : $uri,
            strtolower($method === null ? $_SERVER['REQUEST_METHOD'] : $method),
            $headers === null ? $this->getAllHeaders() : $headers,
            $body === null ? new \Scoop\Http\Message\Stream(fopen('php://input', 'r')) : $body
        );
        $this->serverParams = $serverParams === null ? $_SERVER : $serverParams;
        $this->cookieParams = $cookies === null ? $_COOKIE : $cookies;
        $this->queryParams = $queryParams === null ? $_GET : $queryParams;
        $this->flash = new \Scoop\Http\Message\Server\Flash($referencer === null ? $this->getReferencer() : $referencer);
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
        }
        return $this->body->getData();
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
        if (array_key_exists($name, $this->attributes) && $this->attributes[$name] === $value) {
            return $this;
        }
        $new = clone $this;
        $new->attributes[$name] = $value;
        return $new;
    }

    public function withoutAttribute($name)
    {
        if (!array_key_exists($name, $this->attributes)) {
            return $this;
        }
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
        if ($url instanceof \Scoop\Http\Message\Server\Route) {
            $url->flushMessage($this->flash);
            $url = \Scoop\Context::inject('\Scoop\Http\Router')->getURL($url);
        }
        header("Location:$url", $status);
        exit;
    }

    public function goBack()
    {
        $http_referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $this->flash->get('http');
        if ($http_referer) {
            $this->redirect($http_referer);
        }
    }

    public function isAjax()
    {
        return strpos($this->getHeaderLine('accept'), 'application/json') !== false
        || $this->getHeaderLine('x-requested-with') === 'XMLHttpRequest';
    }

    public function flash()
    {
        return $this->flash;
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
}
