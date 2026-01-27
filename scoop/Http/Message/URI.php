<?php

namespace Scoop\Http\Message;

class URI
{
    private $scheme;
    private $authority;
    private $userInfo;
    private $host;
    private $port;
    private $path;
    private $query;
    private $fragment;

    public function __construct($uri = '')
    {
        $parts = parse_url($uri);
        if ($parts === false) {
            throw new \InvalidArgumentException('Invalid URI', 793);
        }
        $this->scheme = isset($parts['scheme']) ? strtolower($parts['scheme']) : '';
        $this->host = isset($parts['host']) ? strtolower($parts['host']) : '';
        $this->port = isset($parts['port']) ? $parts['port'] : null;
        $this->path = isset($parts['path']) ? $parts['path'] : '';
        $this->query = isset($parts['query']) ? $parts['query'] : '';
        $this->fragment = isset($parts['fragment']) ? $parts['fragment'] : '';
        $this->userInfo = isset($parts['user']) ? $parts['user'] : '';
        if (isset($parts['pass'])) {
            $this->userInfo .= ':' . $parts['pass'];
        }
        $this->authority = $this->getAuthorityFromParts($this->userInfo, $this->host, $this->port);
    }

    public function getScheme()
    {
        return $this->scheme;
    }

    public function getAuthority()
    {
        return $this->authority;
    }

    public function getUserInfo()
    {
        return $this->userInfo;
    }

    public function getHost()
    {
        return $this->host;
    }

    public function getPort()
    {
        return $this->port;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function getQuery()
    {
        return $this->query;
    }

    public function getFragment()
    {
        return $this->fragment;
    }

    public function withScheme($scheme)
    {
        $scheme = strtolower($scheme);
        if ($this->scheme === $scheme) {
            return $this;
        }
        if (!in_array($scheme, array('', 'http', 'https'))) {
            throw new \InvalidArgumentException('Invalid schema', 794);
        }
        $new = clone $this;
        $new->scheme = $scheme;
        $new->authority = $this->getAuthorityFromParts($this->userInfo, $this->host, $this->port);
        return $new;
    }

    public function withUserInfo($user, $password = null)
    {
        $userInfo = $user;
        if ($password !== null) {
            $userInfo .= ':' . $password;
        }
        if ($this->userInfo === $userInfo) {
            return $this;
        }
        $new = clone $this;
        $new->userInfo = $userInfo;
        $new->authority = $this->getAuthorityFromParts($new->userInfo, $new->host, $new->port);
        return $new;
    }

    public function withHost($host)
    {
        $host = strtolower($host);
        if ($this->host === $host) {
            return $this;
        }
        $new = clone $this;
        $new->host = $host;
        $new->authority = $this->getAuthorityFromParts($this->userInfo, $new->host, $this->port);
        return $new;
    }

    public function withPort($port)
    {
        if ($this->port === $port) {
            return $this;
        }
        if ($port !== null && ($port < 1 || $port > 65535)) {
            throw new \InvalidArgumentException('Invalid port', 795);
        }
        $new = clone $this;
        $new->port = $port;
        $new->authority = $this->getAuthorityFromParts($this->userInfo, $new->host, $new->port);
        return $new;
    }

    public function withPath($path)
    {
        $path = preg_replace_callback(
            '/(?:[^a-zA-Z0-9\-\._~!\$&\'\(\)\*\+,;=%:@\/]++|%(?![A-Fa-f0-9]{2}))/',
            function ($matches) {
                return rawurlencode($matches[0]);
            },
            $path
        );
        if ($this->path === $path) {
            return $this;
        }
        if (($this->authority && $path !== '') || strpos($path, '//') === 0) {
            $path = '/' . ltrim($path, '/');
        }
        $new = clone $this;
        $new->path = $path;
        return $new;
    }

    public function withQuery($query)
    {
        if ($this->query === $query) {
            return $this;
        }
        $new = clone $this;
        $new->query = $query;
        return $new;
    }

    public function withFragment($fragment)
    {
        if ($this->fragment === $fragment) {
            return $this;
        }
        $new = clone $this;
        $new->fragment = $fragment;
        return $new;
    }

    public function __toString()
    {
        $uri = '';
        if ($this->scheme !== '') {
            $uri .= $this->scheme . ':';
        }
        if ($this->authority !== '') {
            $uri .= '//' . $this->authority;
        }
        $uri .= $this->path;
        if ($this->query !== '') {
            $uri .= '?' . $this->query;
        }
        if ($this->fragment !== '') {
            $uri .= '#' . $this->fragment;
        }
        return $uri;
    }
    private function getAuthorityFromParts($userInfo, $host, $port)
    {
        $authority = '';
        if ($userInfo !== '') {
            $authority .= $userInfo . '@';
        }
        $authority .= $host;
        if ($port !== null) {
            $authority .= ':' . $port;
        }
        return $authority;
    }
}
