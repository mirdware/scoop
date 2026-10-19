<?php

namespace Scoop\Http\Client;

class Transfer
{
    private $handle;
    private $body;
    private $headers = array();
    private $multi;

    public function __construct($request, array $options)
    {
        try {
            $this->handle = curl_init();
            $this->body = fopen('php://temp', 'r+');
            if (!$this->handle || !$this->body) {
                throw new \RuntimeException('Cannot allocate HTTP transfer.');
            }
            $options = array_replace($options, $this->getOptions($request));
            if (!curl_setopt_array($this->handle, $options)) {
                throw new \RuntimeException('Cannot configure an HTTP transfer.');
            }
        } catch (\Exception $ex) {
            $this->close();
            throw $ex;
        } catch (\Throwable $ex) {
            $this->close();
            throw $ex;
        }
    }

    public function getHandle()
    {
        return $this->handle;
    }

    public function getBody()
    {
        return $this->body;
    }

    public function getId()
    {
        return is_resource($this->handle) ? (int) $this->handle : spl_object_hash($this->handle);
    }

    public function addHeader($handle, $line)
    {
        if (strncmp($line, 'HTTP/', 5) === 0) {
            $this->headers = array();
        } elseif (strpos($line, ':') !== false) {
            list($key, $value) = explode(':', $line, 2);
            $key = trim($key);
            if (!isset($this->headers[$key])) {
                $this->headers[$key] = array();
            }
            $this->headers[$key][] = trim($value);
        }
        return strlen($line);
    }

    public function attach($multi)
    {
        if ($this->multi !== null) {
            throw new \LogicException('HTTP transfer is already attached.');
        }
        if (curl_multi_add_handle($multi, $this->handle) !== CURLM_OK) {
            throw new \RuntimeException('Cannot register an HTTP transfer.');
        }
        $this->multi = $multi;
    }

    public function createResponse()
    {
        rewind($this->body);
        $response = new \Scoop\Http\Message\Response(
            curl_getinfo($this->handle, CURLINFO_HTTP_CODE),
            $this->headers,
            new \Scoop\Http\Message\Stream($this->body)
        );
        $this->body = null;
        return $response;
    }

    public function close()
    {
        if ($this->multi !== null) {
            curl_multi_remove_handle($this->multi, $this->handle);
            $this->multi = null;
        }
        if (is_resource($this->handle)) {
            curl_close($this->handle);
        }
        $this->handle = null;
        if (is_resource($this->body)) {
            fclose($this->body);
        }
        $this->body = null;
    }

    private function getOptions($request)
    {
        $method = strtoupper($request->getMethod());
        $options = array(
            CURLOPT_URL => (string) $request->getUri(),
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_HEADER => false,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_FILE => $this->body,
            CURLOPT_HEADERFUNCTION => array($this, 'addHeader')
        );
        $requestHeaders = array();
        foreach ($request->getHeaders() as $name => $values) {
            $requestHeaders[] = $name . ': ' . implode(', ', $values);
        }
        $options[CURLOPT_HTTPHEADER] = $requestHeaders;
        if ($method === 'HEAD') {
            $options[CURLOPT_NOBODY] = true;
            return $options;
        }
        if ($method === 'GET') {
            return $options;
        }
        if ($method === 'POST') {
            $options[CURLOPT_POST] = true;
        } else {
            $options[CURLOPT_CUSTOMREQUEST] = $method;
        }
        $body = $request->getBody();
        $size = $body->getSize();
        if ($size !== 0) {
            if ($body->isSeekable()) {
                $body->rewind();
            }
            if ($size !== null && $size < 2097152) {
                $options[CURLOPT_POSTFIELDS] = $body->getContents();
            } else {
                $options[CURLOPT_UPLOAD] = true;
                $options[CURLOPT_INFILESIZE] = $size;
                $options[CURLOPT_READFUNCTION] = function ($ch, $fd, $length) use ($body) {
                    return $body->read($length);
                };
            }
        }
        return $options;
    }
}
