<?php

namespace Scoop\Http\Message\Parser;

class Multipart
{
    const READ_SIZE = 65536;
    const MAX_HEADERS = 16384;
    const PREAMBLE = 0;
    const HEADERS = 1;
    const BODY = 2;
    const END = 3;
    private $delimiter;
    private $buffer = "\r\n";
    private $state = self::PREAMBLE;
    private $part;
    private $data = array();
    private $files = array();

    public function __construct($stream, $contentType)
    {
        if (preg_match('/boundary=(?:"([^"]+)"|([^; ]+))/', $contentType, $matches)) {
            $this->delimiter = "\r\n--" . ($matches[1] ? $matches[1] : $matches[2]);
            $this->parse($stream);
        }
    }

    public function getData()
    {
        return $this->data;
    }

    public function getFiles()
    {
        return $this->files;
    }

    private function parse($stream)
    {
        $eof = false;
        while ($this->state !== self::END) {
            do {
                $advanced = $this->advance();
            } while ($advanced && $this->state !== self::END);
            if ($this->state === self::END) {
                break;
            }
            if ($eof) {
                $this->finish(UPLOAD_ERR_PARTIAL);
                break;
            }
            $chunk = $stream->read(self::READ_SIZE);
            $this->buffer .= $chunk;
            $eof = $chunk === '' || $stream->eof();
        }
    }

    private function advance()
    {
        switch ($this->state) {
            case self::PREAMBLE:
                return $this->readPreamble();
            case self::HEADERS:
                return $this->readHeaders();
            default:
                return $this->readBody();
        }
    }

    private function readPreamble()
    {
        $length = strlen($this->delimiter);
        $pos = strpos($this->buffer, $this->delimiter);
        if ($pos === false) {
            $this->buffer = (string) substr($this->buffer, -$length);
            return false;
        }
        if (strlen($this->buffer) < $pos + $length + 2) {
            return false;
        }
        $tail = substr($this->buffer, $pos + $length, 2);
        $this->buffer = (string) substr($this->buffer, $pos + $length + 2);
        $this->state = $tail === "\r\n" ? self::HEADERS : self::END;
        return true;
    }

    private function readHeaders()
    {
        $pos = strpos($this->buffer, "\r\n\r\n");
        if ($pos === false) {
            if (strlen($this->buffer) > self::MAX_HEADERS) {
                $this->state = self::END;
            }
            return false;
        }
        $rawHeaders = substr($this->buffer, 0, $pos);
        $headers = array();
        foreach (explode("\r\n", $rawHeaders) as $line) {
            if (strpos($line, ':') !== false) {
                list($name, $value) = explode(':', $line, 2);
                $headers[strtolower(trim($name))] = trim($value);
            }
        }
        $this->part = new Part($headers);
        $this->buffer = (string) substr($this->buffer, $pos + 4);
        $this->state = self::BODY;
        return true;
    }

    private function readBody()
    {
        $pos = strpos($this->buffer, $this->delimiter);
        if ($pos === false) {
            $safe = strlen($this->buffer) - strlen($this->delimiter);
            if ($safe > 0) {
                $this->part->append(substr($this->buffer, 0, $safe));
                $this->buffer = (string) substr($this->buffer, $safe);
            }
            return false;
        }
        $this->part->append(substr($this->buffer, 0, $pos));
        $this->finish(UPLOAD_ERR_OK);
        $this->buffer = (string) substr($this->buffer, $pos);
        $this->state = self::PREAMBLE;
        return true;
    }

    private function finish($error)
    {
        $part = $this->part;
        $this->part = null;
        if ($part === null || $part->getName() === null) {
            return;
        }
        if ($part->isFile()) {
            $this->store($this->files, $part, $part->toUploadedFile($error));
        } else {
            $this->store($this->data, $part, $part->getValue());
        }
    }

    private function store(&$target, $part, $value)
    {
        if ($part->isArray()) {
            $target[$part->getName()][] = $value;
        } else {
            $target[$part->getName()] = $value;
        }
    }
}
