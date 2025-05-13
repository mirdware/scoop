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
        $this->scheme = isset($parts['scheme']) ? $parts['scheme'] : '';
        $this->host = isset($parts['host']) ? $parts['host'] : '';
        $this->port = isset($parts['port']) ? $parts['port'] : null;
        $this->path = isset($parts['path']) ? $parts['path'] : '';
        $this->query = isset($parts['query']) ? $parts['query'] : '';
        $this->fragment = isset($parts['fragment']) ? $parts['fragment'] : '';
        if (isset($parts['user'])) {
            $this->userInfo = $parts['user'];
            if (isset($parts['pass'])) {
                $this->userInfo .= ':' . $parts['pass'];
            }
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
        if (!in_array($scheme, ['', 'http', 'https'])) {
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
        $new = clone $this;
        $new->userInfo = $userInfo;
        $new->authority = $this->getAuthorityFromParts($new->userInfo, $new->host, $new->port);
        return $new;
    }

    public function withHost($host)
    {
        $new = clone $this;
        $new->host = $host;
        $new->authority = $this->getAuthorityFromParts($this->userInfo, $new->host, $this->port);
        return $new;
    }

    public function withPort($port)
    {
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
        if (preg_match('#[^a-zA-Z0-9/\-._~!$&\'()*+,;=:@%]#', $path)) {
            throw new \InvalidArgumentException('Invalid path', 796);
        }
        $new = clone $this;
        $new->path = $path;
        return $new;
    }

    public function withQuery($query)
    {
        $new = clone $this;
        $new->query = $query;
        return $new;
    }

    public function withFragment($fragment)
    {
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
