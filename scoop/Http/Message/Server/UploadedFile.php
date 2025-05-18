<?php

namespace Scoop\Http\Message\Server;

class UploadedFile
{
    private $clientFilename;
    private $clientMediaType;
    private $error;
    private $file;
    private $moved;
    private $size;
    private $stream;

    public function __construct(
        $file,
        $size,
        $error,
        $clientFilename,
        $clientMediaType
    ) {
        $this->file = $file;
        $this->size = $size;
        $this->error = $error;
        $this->clientFilename = $clientFilename;
        $this->clientMediaType = $clientMediaType;
        $this->moved = false;
        $this->stream = null;
    }

    public function getStream()
    {
        if ($this->moved) {
            throw new \RuntimeException('The file has already been moved', 731);
        }
        if ($this->stream) {
            return $this->stream;
        }
        $resource = fopen($this->file, 'r');
        if (!$resource) {
            throw new \RuntimeException('The file could not be opened for reading', 732);
        }
        return $this->stream = new \Scoop\Http\Message\Stream($resource);
    }

    public function moveTo($targetPath)
    {
        if ($this->moved) {
            throw new \RuntimeException('The file has already been moved', 731);
        }
        if (!is_writable(dirname($targetPath))) {
            throw new \InvalidArgumentException('The destination directory is not writable', 733);
        }
        if ($this->error !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('Error uploading file', 734);
        }
        if (!move_uploaded_file($this->file, $targetPath)) {
            throw new \RuntimeException('The file could not be moved', 735);
        }
        $this->moved = true;
    }

    public function getSize()
    {
        return $this->size;
    }

    public function getError()
    {
        return $this->error;
    }

    public function getClientFilename()
    {
        return $this->clientFilename;
    }

    public function getClientMediaType()
    {
        return $this->clientMediaType;
    }
}
