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
        if (strpos($contentType, 'application/json') !== false) {
            $this->parsedBody = json_decode($body, true);
            return;
        }
        if ($method === 'post') {
            $this->parsedBody = $_POST;
            $this->uploadedFiles = $this->normalizeFiles($_FILES);
            return;
        }
        if (!$body) {
            return;
        }
        if (strpos($contentType, 'application/x-www-form-urlencoded') !== false) {
            parse_str($body, $this->parsedBody);
            return;

        }
        $this->parsedBody = $this->parseMultipartBody($contentType, $body);
    }

    private function parseMultipartBody($contentType, $body)
    {
        $boundary = $this->getBoundary($contentType);
        if (!$boundary) {
            return array();
        }
        $parts = explode('--' . $boundary, $body);
        array_shift($parts);
        $parsedBody = array();
        foreach ($parts as $part) {
            $part = trim($part);
            if ($part !== '' && $part !== '--') {
                $part = rtrim($part, "\r\n");
                if (strpos($part, "\r\n\r\n") !== false) {
                    list($rawHeaders, $partBody) = explode("\r\n\r\n", $part, 2);
                    $headers = $this->parseHeaders($rawHeaders);
                    if (isset($headers['content-disposition'])) {
                        if (preg_match('/name="([^"]+)"/i', $headers['content-disposition'], $nameMatches)) {
                            $fieldName = $nameMatches[1];
                            $fileName = null;
                            if (preg_match('/filename="([^"]+)"/i', $headers['content-disposition'], $filenameMatches)) {
                                $fileName = $filenameMatches[1];
                            }
                            $isArrayField = false;
                            if (substr($fieldName, -2) === '[]') {
                                $fieldName = substr($fieldName, 0, -2);
                                $isArrayField = true;
                            }
                            if ($fileName !== null) {
                                $tmpFilePath = $this->saveFile($partBody);
                                $fileData = new \Scoop\Http\Message\Server\UploadedFile(
                                    isset($tmpFilePath[1]) ? $tmpFilePath[1] : '',
                                    strlen($partBody),
                                    $tmpFilePath[0],
                                    $fileName,
                                    $headers['content-type'] ? $headers['content-type'] : 'application/octet-stream'
                                );
                                if ($isArrayField) {
                                    if (!isset($this->uploadedFiles[$fieldName])) {
                                        $this->uploadedFiles[$fieldName] = array();
                                    }
                                    $this->uploadedFiles[$fieldName][] = $fileData;
                                } else {
                                    $this->uploadedFiles[$fieldName] = $fileData;
                                }
                            } else {
                                if ($isArrayField) {
                                    if (!isset($parsedBody[$fieldName])) {
                                        $parsedBody[$fieldName] = array();
                                    }
                                    $parsedBody[$fieldName][] = $partBody;
                                } else {
                                    $parsedBody[$fieldName] = $partBody;
                                }
                            }
                        }
                    }
                }
            }
        }
        return $parsedBody;
    }

    private function saveFile($content)
    {
        $tmpFilePath = tempnam(sys_get_temp_dir(), 'php_upload_parser_');
        if ($tmpFilePath === false) {
            return array(UPLOAD_ERR_NO_TMP_DIR);
        }
        $fp = fopen($tmpFilePath, 'wb');
        if ($fp === false) {
            unlink($tmpFilePath);
            return array(UPLOAD_ERR_CANT_WRITE);
        }
        $bytesWritten = fwrite($fp, $content);
        fclose($fp);
        if ($bytesWritten === false || $bytesWritten < strlen($content)) {
            if (file_exists($tmpFilePath)) {
                unlink($tmpFilePath);
            }
            return array(UPLOAD_ERR_CANT_WRITE);
        }
        return array(UPLOAD_ERR_OK, $tmpFilePath);
    }

    private function getBoundary($contentType)
    {
        if (preg_match('/boundary=(?:"([^"]+)"|([^; ]+))/', $contentType, $matches)) {
            return $matches[1] ?: $matches[2];
        }
        return null;
    }

    private function parseHeaders($rawHeaders)
    {
        $headers = array();
        $headerLines = explode("\r\n", $rawHeaders);
        foreach ($headerLines as $line) {
            if (strpos($line, ':') !== false) {
                list($name, $value) = explode(':', $line, 2);
                $name = strtolower(trim($name));
                $value = trim($value);
                $headers[$name] = $value;
            }
        }
        return $headers;
    }

    private function normalizeFiles($files)
    {
        $normalized = array();
        foreach ($files as $key => $value) {
            if ($value instanceof UploadedFile) {
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
