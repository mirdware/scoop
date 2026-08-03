<?php

namespace Scoop\Log\Handler;

class Slack
{
    private $formatter;
    private $config;
    private $url;

    public function __construct($formatter, $url, $config = array())
    {
        $this->formatter = $formatter;
        $this->url = $url;
        $this->config = $config;
    }

    public function handle($log)
    {
        $ch = curl_init($this->url);
        $data = json_encode($this->config + array(
            'text' => $this->formatter->format($log)
        ));
        curl_setopt_array($ch, array(
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            // Hardening de v0.8.3:
            CURLOPT_CONNECTTIMEOUT => 1,
            CURLOPT_TIMEOUT => 2,
        ));
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }
}
