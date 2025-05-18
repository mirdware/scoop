<?php

namespace Scoop\Http\Message;

class Request extends \Scoop\Http\Message
{
    private $method;
    private $uri;

    public function __construct($uri, $method, $headers = array(), $body = null)
    {
        parent::__construct(
            $headers,
            $body === null ? new Stream(fopen('php://temp', 'r+')) : $body
        );
        $this->method = strtolower($method);
        $this->uri = $uri;
    }


    public function getMethod()
    {
        return $this->method;
    }

    public function withMethod($method)
    {
        $new = clone $this;
        $new->method = strtoupper($method);
        return $new;
    }

    public function getUri()
    {
        return $this->uri;
    }

    public function withUri(URI $uri, $preserveHost = false)
    {
        $new = clone $this;
        $new->uri = $uri;
        if (!$preserveHost) {
            if ($uri->getHost() !== '' && !$this->hasHeader('Host')) {
                $new = $new->withHeader('Host', $uri->getHost());
            }
        }
        return $new;
    }
}
