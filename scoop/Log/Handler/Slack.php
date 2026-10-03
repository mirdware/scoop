<?php

namespace Scoop\Log\Handler;

class Slack
{
    private $formatter;
    private $uri;
    private $httpClient;
    private $config;

    public function __construct(\Scoop\Http\Client $httpClient, $formatter, $url, $config = array())
    {
        $this->formatter = $formatter;
        $this->config = $config;
        $this->uri = new \Scoop\Http\Message\URI($url);
        $this->httpClient = $httpClient->withOption(CURLOPT_TIMEOUT, 5);
    }

    public function handle($record)
    {
        $body = json_encode($this->config + array(
            'text' => $this->formatter->format($record)
        ));
        if ($body === false) {
            throw new \RuntimeException('Cannot encode Slack log payload');
        }
        $request = new \Scoop\Http\Message\Request($this->uri, 'POST', array(
            'Content-Type' => 'application/json'
        ), $body);
        $response = $this->httpClient->sendRequest($request);
        $status = $response->getStatusCode();
        if ($status < 200 || $status >= 300) {
            throw new \RuntimeException('Slack log delivery failed with HTTP status ' . $status);
        }
        return $response->getBody()->getContents();
    }
}
