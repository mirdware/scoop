<?php

namespace Scoop\Http\Message\Parser;

class Body
{
    private $parsedBody;
    private $uploadedFiles;

    public function __construct($body, $method, $contentType)
    {
        $this->uploadedFiles = array();
        $this->parsedBody = array();
        $this->parse($body, $method, $contentType);
    }

    public function getData()
    {
        return $this->parsedBody;
    }

    public function getFiles()
    {
        return $this->uploadedFiles;
    }

    private function parse($body, $method, $contentType)
    {
        if ($body->isSeekable()) {
            $body->rewind();
        }
        if (strpos($contentType, 'application/json') !== false) {
            $this->parsedBody = json_decode($body->getContents(), true);
            return;
        }
        if (strtoupper($method) === 'POST') {
            $this->parsedBody = $_POST;
            $this->uploadedFiles = $this->normalizeFiles($_FILES);
            return;
        }
        if (strpos($contentType, 'multipart/form-data') !== false) {
            $multipartParser = new Multipart($body, $contentType);
            $this->uploadedFiles = $multipartParser->getFiles();
            $this->parsedBody = $multipartParser->getData();
            return;
        }
        if (strpos($contentType, 'application/x-www-form-urlencoded') !== false) {
            parse_str($body->getContents(), $this->parsedBody);
        }
    }

    private function normalizeFiles($files)
    {
        $normalized = array();
        foreach ($files as $key => $value) {
            if ($value instanceof \Scoop\Http\Message\Server\UploadedFile) {
                $normalized[$key] = $value;
            } elseif (is_array($value) && isset($value['tmp_name'])) {
                if (is_array($value['tmp_name'])) {
                   $normalized[$key] = array();
                   foreach ($value['tmp_name'] as $i => $tmp_name) {
                       $normalized[$key][] = new \Scoop\Http\Message\Server\UploadedFile(
                           $tmp_name,
                           (int)$value['size'][$i],
                           (int)$value['error'][$i],
                           $value['name'][$i],
                           $value['type'][$i]
                       );
                   }
                } else {
                    $normalized[$key] = new \Scoop\Http\Message\Server\UploadedFile(
                        $value['tmp_name'],
                        (int)$value['size'],
                        (int)$value['error'],
                        $value['name'],
                        $value['type']
                    );
                }
            }
        }
        return $normalized;
    }
}
