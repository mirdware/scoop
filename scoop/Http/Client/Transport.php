<?php

namespace Scoop\Http\Client;

class Transport
{
    private $callbacks;
    private $curl;

    public function __construct(array $options)
    {
        $this->curl = isset($options['curl']) ? $options['curl'] : array();
        $this->callbacks = array();
        $callbacks = array('onComplete', 'onFailure');
        foreach ($callbacks as $callback) {
            $this->callbacks[$callback] = isset($options[$callback]) &&
            is_callable($options[$callback]) ?
            $options[$callback] : false;
        }
    }

    public function send($request)
    {
        $transfer = null;
        try {
            $transfer = $this->createTransfer($request);
            $success = curl_exec($transfer->getHandle());
            $response = $this->finishTransfer(
                $transfer,
                $request,
                $success === false ? curl_errno($transfer->getHandle()) : CURLE_OK
            );
        } catch (\Scoop\Http\Exception\Client $error) {
            if ($transfer !== null) {
                $transfer->close();
            }
            $this->notify('onFailure', $error);
            throw $error;
        } catch (\Exception $error) {
            if ($transfer !== null) {
                $transfer->close();
            }
            throw $error;
        } catch (\Throwable $error) {
            if ($transfer !== null) {
                $transfer->close();
            }
            throw $error;
        }
        $transfer->close();
        $this->notify('onComplete', $response);
        return $response;
    }

    public function createTransfer($request)
    {
        return new Transfer($request, $this->curl);
    }

    public function finishTransfer(Transfer $transfer, $request, $errno)
    {
        if ($errno !== CURLE_OK) {
            $message = curl_error($transfer->getHandle());
            $error = in_array($errno, array(
                CURLE_COULDNT_RESOLVE_PROXY, CURLE_COULDNT_RESOLVE_HOST,
                CURLE_COULDNT_CONNECT, CURLE_OPERATION_TIMEOUTED,
                CURLE_SSL_CONNECT_ERROR, CURLE_GOT_NOTHING,
                CURLE_SEND_ERROR, CURLE_RECV_ERROR
            )) ? new \Scoop\Http\Exception\Network($message, $request, $errno) :
            new \Scoop\Http\Exception\Request($message, $request, $errno);
            throw $error;
        }
        return $transfer->createResponse();
    }

    public function notify($callback, $resource, $key = null)
    {
        if ($this->callbacks[$callback]) {
            call_user_func($this->callbacks[$callback], $resource, $key);
        }
    }
}
