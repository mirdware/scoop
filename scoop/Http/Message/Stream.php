<?php

namespace Scoop\Http\Message;

class Stream
{
    private $resource;
    private $readable;
    private $writable;
    private $seekable;
    private $size;

    public function __construct($resource)
    {
        if (!is_resource($resource)) {
            throw new \InvalidArgumentException('Invalid resource', 792);
        }
        $meta = stream_get_meta_data($resource);
        $this->resource = $resource;
        $this->readable = strpbrk($meta['mode'], 'r+') !== false;
        $this->writable = strpbrk($meta['mode'], 'waxc+') !== false;
        $this->seekable = $meta['seekable'];
        $this->size = null;
    }

    public function __toString()
    {
        if (!$this->isReadable()) {
            return '';
        }
        try {
            $this->rewind();
            return $this->getContents();
        } catch (\Exception $e) {
            return '';
        }
    }

    public function close()
    {
        if (is_resource($this->resource)) {
            fclose($this->resource);
        }
        $this->detach();
    }

    public function detach()
    {
        $resource = $this->resource;
        $this->resource = null;
        $this->size = null;
        $this->readable = false;
        $this->writable = false;
        $this->seekable = false;
        return $resource;
    }

    public function getSize()
    {
        if ($this->size !== null) {
            return $this->size;
        }
        if (is_resource($this->resource)) {
            $stats = fstat($this->resource);
            if ($stats !== false) {
                $this->size = $stats['size'];
                return $this->size;
            }
        }
        return null;
    }

    public function tell()
    {
        if (!is_resource($this->resource)) {
            throw new \RuntimeException('There are no resources attached to the stream', 721);
        }
        $position = ftell($this->resource);
        if ($position === false) {
            throw new \RuntimeException('An error occurred while getting the pointer position', 722);
        }
        return $position;
    }

    public function eof()
    {
        if (!is_resource($this->resource)) {
            return true;
        }
        return feof($this->resource);
    }

    public function isSeekable()
    {
        return $this->seekable;
    }

    public function seek($offset, $whence = SEEK_SET)
    {
        if (!$this->isSeekable()) {
            throw new \RuntimeException('The stream is not sekeable.', 723);
        }
        if (fseek($this->resource, $offset, $whence) === -1) {
            throw new \RuntimeException('An error occurred while seeking the stream', 724);
        }
    }

    public function rewind()
    {
        $this->seek(0);
    }

    public function isWritable()
    {
        return $this->writable;
    }

    public function write($string)
    {
        if (!$this->isWritable()) {
            throw new \RuntimeException('The stream is not writable', 725);
        }
        $bytesWritten = fwrite($this->resource, $string);
        if ($bytesWritten === false) {
            throw new \RuntimeException('An error occurred while writing to the stream', 726);
        }
        $this->size = null;
        return $bytesWritten;
    }

    public function isReadable()
    {
        return $this->readable;
    }

    public function read($length)
    {
        if (!$this->isReadable()) {
            throw new \RuntimeException('The stream is not readable', 727);
        }
        $result = fread($this->resource, $length);
        if ($result === false) {
            throw new \RuntimeException('An error occurred while reading from the stream', 728);
        }
        return $result;
    }

    public function getContents()
    {
        if (!is_resource($this->resource)) {
            return '';
        }
        $contents = stream_get_contents($this->resource);
        if ($contents === false) {
            return '';
        }
        return $contents;
    }

    public function getMetadata($key = null)
    {
        if (!is_resource($this->resource)) {
            return null;
        }
        $metadata = stream_get_meta_data($this->resource);
        if ($key === null) {
            return $metadata;
        }
        return isset($metadata[$key]) ? $metadata[$key] : null;
    }
}
