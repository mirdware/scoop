<?php

namespace Scoop\Http;

abstract class Message
{
    private $headers;
    private $body;
    private $protocolVersion;

    public function __construct($headers, $body)
    {
        $this->headers = array();
        foreach ($headers as $name => $value) {
            $name = strtolower($name);
            $this->headers[$name] = is_array($value) ? $value : array($value);
        }
        if (!$body instanceof \Scoop\Http\Message\Stream) {
            $stream = new \Scoop\Http\Message\Stream(fopen('php://temp', 'r+'));
            $stream->write($body);
            $body = $stream;
        }
        $this->body = $body;
        $this->protocolVersion = '1.1';
    }

    public function getProtocolVersion()
    {
        return $this->protocolVersion;
    }

    public function withProtocolVersion($version)
    {
        if ($this->protocolVersion === $version) {
            return $this;
        }
        $new = clone $this;
        $new->protocolVersion = $version;
        return $new;
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    public function hasHeader($name)
    {
        $name = strtolower($name);
        return isset($this->headers[$name]);
    }

    public function getHeader($name)
    {
        $name = strtolower($name);
        if ($this->hasHeader($name)) {
            return $this->headers[$name];
        }
        return array();
    }

    public function getHeaderLine($name)
    {
        return implode(', ', $this->getHeader($name));
    }

    public function withHeader($name, $value)
    {
        $name = strtolower($name);
        $new = clone $this;
        $new->headers[$name] = is_array($value) ? $value : array($value);
        return $new;
    }

    public function withAddedHeader($name, $value)
    {
        $name = strtolower($name);
        $new = clone $this;
        if ($new->hasHeader($name)) {
            $new->headers[$name] = array_merge($new->getHeader($name), is_array($value) ? $value : array($value));
        } else {
            $new->headers[$name] = is_array($value) ? $value : array($value);
        }
        return $new;
    }

    public function withoutHeader($name)
    {
        $new = clone $this;
        $name = strtolower($name);
        foreach (array_keys($new->headers) as $key) {
            if (strtolower($key) === $name) {
                unset($new->headers[$key]);
                break;
            }
        }
        return $new;
    }

    public function getBody()
    {
        return $this->body;
    }

    public function withBody(\Scoop\Http\Message\Stream $body)
    {
        $new = clone $this;
        $new->body = $body;
        return $new;
    }
}
