<?php

namespace Scoop\Http\Message;

class Request extends \Scoop\Http\Message
{
    private $method;
    private $uri;

    public function __construct($uri, $method, $headers = array(), $body = '')
    {
        parent::__construct($headers,$body);
        $this->method = strtolower($method);
        $this->uri = is_string($uri) ? new URI($uri) : $uri;
    }

    public function getMethod()
    {
        return $this->method;
    }

    public function withMethod($method)
    {
        $method = strtolower($method);
        if ($this->method === $method) {
            return $this;
        }
        $new = clone $this;
        $new->method = $method;
        return $new;
    }

    public function getUri()
    {
        return $this->uri;
    }

    public function withUri(URI $uri, $preserveHost = false)
    {
        if ($this->uri === $uri) {
            return $this;
        }
        $new = clone $this;
        $new->uri = $uri;
        if (!$preserveHost || !$this->hasHeader('Host')) {
            $host = $uri->getHost();
            if ($host !== '') {
                $port = $uri->getPort();
                if ($port !== null) {
                    $host .= ":$port";
                }
                return $new->withHeader('Host', $host);
            }
        }
        return $new;
    }
}
