<?php

namespace Scoop\Http;

use function PHPUnit\Framework\isNumeric;

abstract class Message
{
    private $body;
    private $protocolVersion = '1.1';
    private $headers = array();
    private $headerNames = array();

    public function __construct($headers, $body)
    {
        foreach ($headers as $name => $value) {
            $key = strtolower($name);
            $value = is_array($value) ? $value : array($value);
            $this->validateHeader($name, $value);
            if (isset($this->headers[$key])) {
                $this->headers[$key] = array_merge($this->headers[$key], $value);
            } else {
                $this->headerNames[$key] = $name;
                $this->headers[$key] = $value;
            }
        }
        if (!$body instanceof \Scoop\Http\Message\Stream) {
            $stream = new \Scoop\Http\Message\Stream(fopen('php://temp', 'r+'));
            $stream->write($body);
            $body = $stream;
        }
        $this->body = $body;
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
        if (!is_numeric($version)) {
            throw new \InvalidArgumentException("$version not is a number");
        }
        $new = clone $this;
        $new->protocolVersion = $version;
        return $new;
    }

    public function getHeaders()
    {
        $headers = array();
        foreach ($this->headerNames as $key => $name) {
            $headers[$name] = $this->headers[$key];
        }
        return $headers;
    }

    public function hasHeader($name)
    {
        $name = strtolower($name);
        return isset($this->headers[$name]);
    }

    public function getHeader($name)
    {
        $name = strtolower($name);
        if (isset($this->headers[$name])) {
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
        $key = strtolower($name);
        $new = clone $this;
        $new->headerNames[$key] = $name;
        $new->headers[$key] = is_array($value) ? $value : array($value);
        $this->validateHeader($name, $new->headers[$key]);
        return $new;
    }

    public function withAddedHeader($name, $value)
    {
        $key = strtolower($name);
        $new = clone $this;
        if (isset($new->headers[$key])) {
            $new->headers[$key] = array_merge($new->getHeader($key), is_array($value) ? $value : array($value));
        } else {
            $new->headers[$key] = is_array($value) ? $value : array($value);
            $new->headerNames[$key] = $name;
        }
        $this->validateHeader($name, $new->headers[$key]);
        return $new;
    }

    public function withoutHeader($name)
    {
        $new = clone $this;
        $name = strtolower($name);
        foreach (array_keys($new->headers) as $key) {
            if (strtolower($key) === $name) {
                unset($new->headers[$key], $new->headerNames[$key]);
                break;
            }
        }
        return $new;
    }

    public function getBody()
    {
        return $this->body;
    }

    public function withBody($body)
    {
        $new = clone $this;
        $new->body = $body;
        return $new;
    }

    private function validateHeader($name, $values)
    {
        if (!preg_match('/^[a-zA-Z0-9\'`#$%&*+.^_|~!-]+$/', $name)) {
            throw new \InvalidArgumentException("Invalid header name: $name");
        }
        foreach ($values as $value) {
            if (preg_match('/(?:(?<!\r)\n|\r(?!\n)|\r\n(?![ \t])|\x00)/', (string) $value)) {
                throw new \InvalidArgumentException("Invalid header value for $name");
            }
        }
    }
}
