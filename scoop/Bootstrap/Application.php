<?php

namespace Scoop\Bootstrap;

class Application
{
    private $environment;
    private $dispatcher;
    private $logger;
    private $entityManager;

    public function __construct()
    {
        $this->environment = \Scoop\Context::inject('\Scoop\Bootstrap\Environment');
        $this->dispatcher = \Scoop\Context::inject('\Scoop\Event\Dispatcher');
        $this->logger = \Scoop\Context::inject('\Scoop\Log\Logger');
        $this->entityManager = \Scoop\Context::inject('\Scoop\Persistence\Entity\Manager');
        $this->enableCORS();
    }

    public function run()
    {
        $requestType = $this->environment->getConfig('request', '\Scoop\Http\Message\Server\Request');
        $router = \Scoop\Context::inject('\Scoop\Http\Router');
        $request = \Scoop\Context::inject($requestType);
        try {
            $response = $router->route($request);
            $this->entityManager->flush();
        } catch (\Exception $ex) {
            $response = $this->manageError($ex, $request->isAjax());
        } catch (\Throwable $ex) {
            $response = $this->manageError($ex, $request->isAjax());
        }
        $this->printResponse($response);
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }
        $this->terminate();
    }

    public function terminate()
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        try {
            $this->dispatcher->dispatch(new \Scoop\Http\Event\RequestFinished());
        } catch (\Exception $ex) {
            error_log($ex);
        } catch (\Throwable $ex) {
            error_log($ex);
        }
        $this->entityManager->clean();
        $this->logger->flush();
    }

    private function manageError($ex, $isAjax)
    {
        $exceptionManager = \Scoop\Context::inject('\Scoop\Http\Error\Mapper');
        $status = $exceptionManager->getStatusCode($ex);
        $this->dispatcher->dispatch(new \Scoop\Http\Event\ErrorOccurred($ex, $status));
        \Scoop\Context::reset();
        if ($ex instanceof \Scoop\Http\Exception\Unprocessable) {
            return $ex->getResponse();
        }
        if ($status) {
            return $exceptionManager->map($ex, $isAjax, $status);
        }
        $this->terminate();
        throw $ex;
    }

    private function printResponse($response)
    {
        $ignore = array(
            'transfer-encoding' => 1,
            'content-encoding' => 1,
            'connection' => 1,
            'keep-alive' => 1,
            'proxy-authenticate' => 1,
            'proxy-authorization' => 1,
            'te' => 1,
            'trailers' => 1,
            'upgrade' => 1
        );
        http_response_code($response->getStatusCode());
        $headers = $response->getHeaders();
        foreach ($headers as $name => $values) {
            if (!isset($ignore[strtolower($name)])) {
                foreach ($values as $value) {
                    header("$name: $value", false);
                }
            }
        }
        $body = $response->getBody();
        $body->rewind();
        $resource = $body->detach();
        fpassthru($resource);
        fclose($resource);
    }

    /**
     * @deprecated
     * @see middleware CorsGuard
     * @since 0.8.1
     */
    private function enableCORS()
    {
        $cors = $this->environment->getConfig('cors');
        if (!$cors) {
            return;
        }
        if (isset($_SERVER['HTTP_ORIGIN'])) {
            $origin = isset($cors['origin']) ?
            array_map('trim', explode(',', $cors['origin'])) :
            array($_SERVER['HTTP_ORIGIN']);
            if (in_array($_SERVER['HTTP_ORIGIN'], $origin)) {
                header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
                header('Access-Control-Allow-Credentials: true');
                header('Access-Control-Max-Age: 86400');
            }
        }
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])) {
                $methods = isset($cors['methods']) ?
                $cors['methods'] :
                $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'];
                header("Access-Control-Allow-Methods: $methods");
            }
            if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
                $headers = isset($cors['headers']) ?
                $cors['headers'] :
                $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'];
                header("Access-Control-Allow-Headers: $headers");
            }
            exit;
        }
    }
}
