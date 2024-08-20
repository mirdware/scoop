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
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }
}
