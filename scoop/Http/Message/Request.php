<?php

namespace Scoop\Http\Message;

class Request extends \Scoop\Http\Message
{
    private $method;
    private $uri;
    private $requestTarget;

    public function __construct($uri, $method, $headers = array(), $body = '')
    {
        $this->uri = is_string($uri) ? new URI($uri) : $uri;
        $this->method = $method;
        $port = $this->uri->getPort();
        $headers += array('Host' => $this->uri->getHost() . ($port ? ":$port" : ''));
        parent::__construct($headers, $body);
        $target = $this->uri->getPath();
        if ($target === '') {
            $target = '/';
        }
        if ($this->uri->getQuery() !== '') {
            $target .= '?' . $this->uri->getQuery();
        }
        $this->requestTarget = $target;
    }

    public function getMethod()
    {
        return $this->method;
    }

    public function withMethod($method)
    {
        if ($this->method === $method) {
            return $this;
        }
        $new = clone $this;
        $new->method = $method;
        return $new;
    }

    public function withRequestTarget($requestTarget)
    {
        if ($requestTarget === $this->requestTarget) {
            return $this;
        }
        $new = clone $this;
        $new->requestTarget = $requestTarget;
        return $new;
    }

    public function getRequestTarget()
    {
        return $this->requestTarget;
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
        $port = $this->uri->getPort();
        return $new->withHeader('Host', $uri->getHost() . ($port ? ":$port" : ''));
    }
}
