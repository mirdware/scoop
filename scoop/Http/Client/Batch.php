<?php

namespace Scoop\Http\Client;

class Batch
{
    private $transport;
    private $waitTime;
    private $concurrency;

    public function __construct(Transport $transport, array $options)
    {
        $this->transport = $transport;
        $this->waitTime = isset($options['waitTime']) ? $options['waitTime'] : 0.3;
        $this->concurrency = isset($options['concurrency']) &&
        $options['concurrency'] > 0 ?
        $options['concurrency'] : 5;
    }

    public function send(array $requests)
    {
        $responses = array();
        $errors = array();
        if (!$requests) {
            return new \Scoop\Http\Client\Result($responses, $errors);
        }
        $queue = array_keys($requests);
        $next = 0;
        $active = array();
        $multi = curl_multi_init();
        try {
            while ($next < count($queue) || $active) {
                while ($next < count($queue) && count($active) < $this->concurrency) {
                    $key = $queue[$next++];
                    $transfer = $this->transport->createTransfer($requests[$key]);
                    $id = $transfer->getId();
                    $active[$id] = array($key, $transfer);
                    $transfer->attach($multi);
                }
                if (!$active) {
                    continue;
                }
                do {
                    $status = curl_multi_exec($multi, $running);
                } while ($status === CURLM_CALL_MULTI_PERFORM);
                if ($status !== CURLM_OK) {
                    throw new \RuntimeException('Cannot execute HTTP batch.', $status);
                }
                while ($info = curl_multi_info_read($multi)) {
                    $handle = $info['handle'];
                    $id = is_resource($handle) ? (int) $handle : spl_object_hash($handle);
                    list($key, $transfer) = $active[$id];
                    try {
                        $responses[$key] = $this->transport->finishTransfer(
                            $transfer,
                            $requests[$key],
                            $info['result']
                        );
                        $this->transport->notify('onComplete', $responses[$key], $key);
                    } catch (\Scoop\Http\Exception\Client $error) {
                        $errors[$key] = $error;
                        $this->transport->notify('onFailure', $error, $key);
                    }
                    $transfer->close();
                    unset($active[$id]);
                }
                if ($next < count($queue) && count($active) < $this->concurrency) {
                    continue;
                }
                if ($active) {
                    if (curl_multi_select($multi, $this->waitTime) === -1) {
                        usleep((int) ($this->waitTime * 1000000));
                    }
                }
            }
        } catch (\Exception $ex) {
            $this->closeBatch($multi, $active);
            throw $ex;
        } catch (\Throwable $ex) {
            $this->closeBatch($multi, $active);
            throw $ex;
        }
        $this->closeBatch($multi, $active);
        return new Result($responses, $errors);
    }

    private function closeBatch($multi, $active)
    {
        foreach ($active as $entry) {
            $entry[1]->close();
        }
        if (is_resource($multi)) {
            curl_multi_close($multi);
        }
    }
}
