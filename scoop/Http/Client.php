<?php

namespace Scoop\Http;

class Client
{
    private $transport;
    private $batch;
    private $options;

    public function __construct(array $options = array())
    {
        $this->configure($options);
    }

    public function sendRequest($request)
    {
        return $this->transport->send($request);
    }

    public function sendAllRequests(array $requests)
    {
        return $this->batch->send($requests);
    }

    public function withOptions(array $options)
    {
        $newOptions = array_replace_recursive($this->options, $options);
        if ($this->options === $newOptions) {
            return $this;
        }
        $new = clone $this;
        $new->configure($newOptions);
        return $new;
    }

    private function configure($options)
    {
        $this->transport = new Client\Transport($options);
        $this->batch = new Client\Batch($this->transport, $options);
        $this->options = $options;
    }
}
