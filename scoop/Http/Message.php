<?php

namespace Scoop\Http;

abstract class Message
{
    private $headers;
    private $body;
    private $protocolVersion;

    public function __construct($headers, $body)
    {
        $this->headers = $headers;
        $this->body = $body;
        $this->protocolVersion = '1.1';
    }

    public function getProtocolVersion()
    {
        return $this->protocolVersion;
    }

    public function withProtocolVersion($version)
    {
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
        foreach (array_keys($this->headers) as $key) {
            if (strtolower($key) === $name) {
                return true;
            }
        }
        return false;
    }

    public function getHeader($name)
    {
         $name = strtolower($name);
        foreach (array_keys($this->headers) as $key) {
            if (strtolower($key) === $name) {
                return $this->headers[$key];
            }
        }
        return array();
    }

    public function getHeaderLine($name)
    {
        return implode(', ', $this->getHeader($name));
    }

    public function withHeader($name, $value)
    {
        $new = clone $this;
        $new->headers[$name] = is_array($value) ? $value : array($value);
        return $new;
    }

    public function withAddedHeader($name, $value)
    {
        $new = clone $this;
        $name = strtolower($name);
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
