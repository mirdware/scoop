<?php

namespace Scoop\Http\Message\Parser;

class Part
{
    private $name;
    private $isArray = false;
    private $filename;
    private $type = 'application/octet-stream';
    private $value = '';
    private $size = 0;
    private $error = UPLOAD_ERR_OK;
    private $path = '';
    private $handle;

    public function __construct($headers)
    {
        if (
            !isset($headers['content-disposition']) ||
            !preg_match('/(?:^|[;\s])name="([^"]+)"/i', $headers['content-disposition'], $name)
        ) {
            return;
        }
        $this->name = $name[1];
        $this->isArray = substr($this->name, -2) === '[]';
        if ($this->isArray) {
            $this->name = substr($this->name, 0, -2);
        }
        if (isset($headers['content-type'])) {
            $this->type = $headers['content-type'];
        }
        if (preg_match('/(?:^|[;\s])filename="([^"]+)"/i', $headers['content-disposition'], $file)) {
            $this->filename = $file[1];
            $this->openTemporary();
        }
    }

    public function __destruct()
    {
        $this->closeHandle();
        if ($this->path !== '' && file_exists($this->path)) {
            unlink($this->path);
        }
        $this->path = '';
    }

    public function getName()
    {
        return $this->name;
    }

    public function isArray()
    {
        return $this->isArray;
    }

    public function isFile()
    {
        return $this->filename !== null;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function append($data)
    {
        if ($this->name === null || $data === '' || $data === false) {
            return;
        }
        if ($this->filename === null) {
            $this->value .= $data;
            return;
        }
        if ($this->handle === null) {
            return;
        }
        $written = fwrite($this->handle, $data);
        if ($written === false || $written < strlen($data)) {
            $this->error = UPLOAD_ERR_CANT_WRITE;
            $this->__destruct();
            return;
        }
        $this->size += $written;
    }

    public function toUploadedFile($error = UPLOAD_ERR_OK)
    {
        $error = $this->error !== UPLOAD_ERR_OK ? $this->error : $error;
        if ($error !== UPLOAD_ERR_OK) {
            $this->__destruct();
        }
        $this->closeHandle();
        $path = $this->path;
        $this->path = '';
        return new \Scoop\Http\Message\Server\UploadedFile(
            $path,
            $this->size,
            $error,
            $this->filename,
            $this->type,
            true
        );
    }

    private function openTemporary()
    {
        $path = tempnam(sys_get_temp_dir(), 'php_upload_parser_');
        if ($path === false) {
            $this->error = UPLOAD_ERR_NO_TMP_DIR;
            return;
        }
        $handle = fopen($path, 'wb');
        if ($handle === false) {
            unlink($path);
            $this->error = UPLOAD_ERR_CANT_WRITE;
            return;
        }
        $this->path = $path;
        $this->handle = $handle;
    }

    private function closeHandle()
    {
        if (is_resource($this->handle)) {
            fclose($this->handle);
        }
        $this->handle = null;
    }
}
