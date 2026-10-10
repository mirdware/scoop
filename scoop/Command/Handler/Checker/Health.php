<?php

namespace Scoop\Command\Handler\Checker;

class Health
{
    private $httpClient;
    private $writer;

    public function __construct(\Scoop\Command\Writer $writer, \Scoop\Http\Client $httpClient)
    {
        $this->httpClient = $httpClient;
        $this->writer = $writer;
    }

    public function execute(\Scoop\Command\Request $command)
    {
        $path = trim($command->getOption('path', ''), '/');
        $response = $this->httpClient
            ->withOption(CURLOPT_CONNECTTIMEOUT, 2)
            ->withOption(CURLOPT_TIMEOUT, 5)
            ->sendRequest(
            new \Scoop\Http\Message\Request(
                new \Scoop\Http\Message\URI(
                    $command->getOption('schema', 'http') . '://' .
                    trim($command->getOption('host', '127.0.0.1'), '/') . ':' .
                    $command->getOption('port', '80') . '/' .
                    ($path === '' ? '' : $path . '/')
                ),
                'GET'
            )
        );
        $status = $response->getStatusCode();
        $body = $response->getBody()->getContents();
        if ($status !== 200) {
            throw new \RuntimeException("Scoop healthcheck failed: HTTP $status $body");
        }
        $this->writer->write($body);
    }

    public function help()
    {
        $this->writer->write(
            'Check the health of application status via an endpoint.',
            '',
            'Options:',
            '--schema => URI scheme: http or https [http]',
            '--host => Target host or IP address [127.0.0.1]',
            '--port => Target port [80]',
            '--path => Health check endpoint path'
        );
    }
}
