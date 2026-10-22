<?php

namespace Scoop\Bootstrap;

use OpenApi\Spec\Response;
use Scoop\View\Message;

class Application
{
    private static $loader;
    private $context;
    private $injector;
    private $dispatcher;
    private $request;
    private $response;
    private $error;

    public function __construct($fileContext = '')
    {
        $default = array();
        if (class_exists('\Scoop\Context')) {
            if (\Scoop\Context::getLoader()) {
                self::$loader = \Scoop\Context::getLoader();
            }
            $default = \Scoop\Context::get();
        }
        $options = array_merge(array(
            'config' => 'app/config',
            'storage' => 'app/storage',
            'stateless' => false
        ), $fileContext ? require $fileContext . '.php' : $default);
        if (!isset(self::$loader)) {
            self::$loader = $this->load($options['storage']);
        }
        \Scoop\Context::load($this);
        $this->context = new \Scoop\Context($options);
        $this->injector = $this->context->getInjector();
        $this->dispatcher = $this->inject('Scoop\Event\Dispatcher');
        if (isset($_SERVER['HTTP_HOST'])) {
            $this->enableCORS();
        }
    }

    public function run()
    {
        $requestType = $this->context->getConfig('request', 'Scoop\Http\Message\Server\Request');
        $request = $this->injector->get($requestType);
        $router = $this->injector->get('Scoop\Http\Router');
        $this->injector->get('Scoop\Bootstrap\Configuration')->setUp();
        \Scoop\View\Service::setUp($this->context);
        $this->request = $request;
        $this->error = null;
        $this->response = null;
        try {
            $this->response = $router->route($request);
            $entityManager = $this->findDependency('Scoop\Persistence\Entity\Manager');
            if ($entityManager) {
                $entityManager->flush();
            }
        } catch (\Exception $ex) {
            $this->response = $this->manageError($ex, $request->isAjax());
        } catch (\Throwable $ex) {
            $this->response = $this->manageError($ex, $request->isAjax());
        }
        $this->printResponse();
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }
        $this->terminate();
    }

    public function execute(array $argv)
    {
        $commandName = array_shift($argv);
        $trace = $commandName === '--trace';
        if ($trace) {
            $commandName = array_shift($argv);
        }
        try {
            $writer = $this->injector->get('Scoop\Command\Writer');
            $commandBus = $this->injector->get('Scoop\Command\Bus');
            if ($commandName !== null && preg_match('/^[a-zA-Z]/', $commandName)) {
                $commandBus->dispatch($commandName, $argv);
                $entityManager = $this->findDependency('Scoop\Persistence\Entity\Manager');
                if ($entityManager) {
                    $entityManager->flush();
                }
                return 0;
            }
            $commands = $commandBus->getCommands();
            $writer->write(
                'ICE/SCOOP version 0.8.6',
                '',
                'Commands:'
            );
            $maxlength = max(array_map('strlen', array_keys($commands)));
            foreach ($commands as $command => $controller) {
                $command = str_pad($command, $maxlength);
                $writer->write("$command => <link:$controller.php!>");
            }
            $writer->write(
                '',
                'Options:',
                '--trace => show exception stack when execute a command',
                '',
                'Run app/ice COMMAND --help for more information'
            );
            return 0;
        } catch (\Exception $ex) {
            return $this->writeError($writer, $ex, $trace);
        } catch (\Throwable $ex) {
            return $this->writeError($writer, $ex, $trace);
        }
    }

    public function terminate()
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        try {
            if (isset($this->request)) {
                $this->dispatcher->dispatch(new \Scoop\Http\Event\RequestFinished(
                    $this->request,
                    $this->response,
                    $this->error
                ));
            }
        } catch (\Exception $ex) {
            error_log($ex);
        } catch (\Throwable $ex) {
            error_log($ex);
        }
        \Scoop\View\Service::reset();
        $logger = $this->findDependency('Scoop\Log\Logger');
        $entityManager = $this->findDependency('Scoop\Persistence\Entity\Manager');
        if ($entityManager) {
            $entityManager->clean();
        }
        if ($logger) {
            $logger->flush();
        }
        $this->injector->clean();
    }

    /**
     * @deprecated
     */
    public function inject($id)
    {
        return $this->injector->get($id);
    }

    private function printResponse()
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
        http_response_code($this->response->getStatusCode());
        $headers = $this->response->getHeaders();
        foreach ($headers as $name => $values) {
            if (!isset($ignore[strtolower($name)])) {
                foreach ($values as $value) {
                    header("$name: $value", false);
                }
            }
        }
        $body = $this->response->getBody();
        $body->rewind();
        $resource = $body->detach();
        fpassthru($resource);
        fclose($resource);
    }

    private function writeError($writer, $ex, $trace)
    {
        $code = intval($ex->getCode());
        $message = $ex->getMessage() . ($trace ? PHP_EOL . $ex->getTraceAsString() : '');
        if (isset($writer)) {
            $writer->withError()->write("<danger:[ERROR]!> <error:$message!>");
        } else {
            error_log($message);
        }
        \Scoop\Context::reset();
        return $code ? $code : 1;
    }

    private function findDependency($id)
    {
        $method = 'contains';
        if (!is_callable(array($this->injector, $method)) || $this->injector->$method($id)) {
            return $this->inject($id);
        }
    }

    private function manageError($ex, $isAjax)
    {
        $this->error = $ex;
        $exceptionManager = $this->injector->get('Scoop\Http\Error\Mapper');
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

    private function load($storagePath)
    {
        if (is_readable('vendor/autoload.php')) {
            return require 'vendor/autoload.php';
        }
        require 'scoop/Bootstrap/Loader.php';
        require 'scoop/Bootstrap/Loader/JsonParser.php';
        $loader = new \Scoop\Bootstrap\Loader();
        $path = trim($storagePath, '/') . '/cache/json/';
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
        $jsonLoader = new \Scoop\Bootstrap\Loader\JsonParser($path);
        $conf = $jsonLoader->load('composer');
        if (isset($conf['autoload']['psr-4'])) {
            foreach ($conf['autoload']['psr-4'] as $key => $value) {
                $loader->set($key, $value);
            }
        }
        $loader->register(true);
        return $loader;
    }

    /**
     * @deprecated
     * @see middleware CorsGuard
     * @since 0.8.1
     */
    private function enableCORS()
    {
        $cors = $this->context->getConfig('cors');
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
