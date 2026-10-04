<?php

namespace Scoop\Log\Handler;

class Slack
{
    private $formatter;
    private $uri;
    private $httpClient;
    private $config;
    private $records = array();

    public function __construct(\Scoop\Http\Client $httpClient, $formatter, $url, $config = array())
    {
        $this->formatter = $formatter;
        $this->config = $config;
        $this->uri = new \Scoop\Http\Message\URI($url);
        $this->httpClient = $httpClient->withOption(CURLOPT_TIMEOUT, 5);
    }

    public function handle($record)
    {
        $this->records[] = $record;
    }

    public function flush()
    {
        $records = $this->records;
        $this->records = array();
        foreach ($records as $record) {
            $body = json_encode($this->config + array(
                'text' => $this->formatter->format($record)
            ));
            try {
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
            } catch (\Exception $ex) {
                error_log($ex);
            } catch (\Throwable $ex) {
                error_log($ex);
            }
        }
    }
}
