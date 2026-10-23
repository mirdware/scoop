<?php

namespace Scoop\Bootstrap;

use OpenApi\Spec\Response;
use Scoop\View\Message;

class Application
{
    private static $loader;
    private $context;
    private $injector;
    private $stateful;
    private $request;
    private $response;
    private $error;
    private $lifecycle;
    private $terminated;

    public function __construct($fileContext)
    {
        $fileContext = $fileContext . '.php';
        $options = array_merge(array(
            'map' => 'app/config',
            'storage' => 'app/storage',
            'stateful' => false
        ), is_readable($fileContext) ? require $fileContext : array());
        if (!isset(self::$loader)) {
            self::$loader = $this->load($options['storage']);
        }
        $this->stateful = $options['stateful'];
        $this->lifecycle = new Lifecycle();
        $this->context = new \Scoop\Context($this->lifecycle, $options);
        $this->injector = $this->context->getInjector();
    }

    public function run()
    {
        $this->terminated = false;
        $this->error = null;
        $this->response = null;
        if ($this->stateful && session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $requestType = $this->context->getConfig('request', 'Scoop\Http\Message\Server\Request');
        $router = $this->injector->get('Scoop\Http\Router');
        $this->request = $this->injector->get($requestType);
        $this->injector->get('Scoop\Bootstrap\Configuration')->setUp();
        \Scoop\View\Service::setUp($this->context);
        try {
            $this->response = $router->route($this->request);
            $this->terminated = true;
            $this->lifecycle->execute($this, 'complete', true);
        } catch (\Exception $ex) {
            $this->response = $this->manageError($ex, $this->request->isAjax());
        } catch (\Throwable $ex) {
            $this->response = $this->manageError($ex, $this->request->isAjax());
        }
        $this->printResponse();
    }

    public function execute(array $argv)
    {
        $code = 0;
        $ex = null;
        $commandName = array_shift($argv);
        $trace = $commandName === '--trace';
        if ($trace) {
            $commandName = array_shift($argv);
        }
        $this->dispatchEvent(new \Scoop\Command\Event\ExecutionStarted($commandName, $argv));
        try {
            $writer = $this->injector->get('Scoop\Command\Writer');
            $commandBus = $this->injector->get('Scoop\Command\Bus');
            if ($commandName !== null && preg_match('/^[a-zA-Z]/', $commandName)) {
                $commandBus->dispatch($commandName, $argv);
                $this->terminated = true;
                $this->lifecycle->execute($this, 'complete', true);
            } else {
                $commands = $commandBus->getCommands();
                $writer->write(
                    'ICE/SCOOP version 0.9.0',
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
            }
        } catch (\Exception $ex) {
            $code = $this->writeError($writer, $ex, $trace);
        } catch (\Throwable $ex) {
            $code = $this->writeError($writer, $ex, $trace);
        }
        $this->dispatchEvent(new \Scoop\Command\Event\ExecutionFinished($commandName, $argv, $code, $ex));
        return $code;
    }

    public function terminate()
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        if (isset($this->request)) {
            $this->dispatchEvent(new \Scoop\Http\Event\RequestFinished(
                $this->request,
                $this->response,
                $this->error
            ));
        }
        $this->terminated = true;
        \Scoop\View\Service::reset();
        $this->lifecycle->execute($this, 'terminate', false);
        $_SESSION = array();
        $this->injector->clean();
        $this->terminated = false;
    }

    public function isShuttingDown()
    {
        return $this->terminated;
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

    private function manageError($ex, $isAjax)
    {
        $this->error = $ex;
        $this->terminated = true;
        $this->lifecycle->execute($this, 'rollback', false);
        $exceptionManager = $this->injector->get('Scoop\Http\Error\Mapper');
        $status = $exceptionManager->getStatusCode($ex);
        if ($ex instanceof \Scoop\Http\Exception\Unprocessable) {
            return $ex->getResponse();
        }
        if ($status) {
            return $exceptionManager->map($ex, $isAjax, $status);
        }
        throw $ex;
    }

    private function writeError($writer, $ex, $trace)
    {
        $this->terminated = true;
        $code = intval($ex->getCode());
        $message = $ex->getMessage() . ($trace ? PHP_EOL . $ex->getTraceAsString() : '');
        if (isset($writer)) {
            $writer->withError()->write("<danger:[ERROR]!> <error:$message!>");
        } else {
            error_log($message);
        }
        $this->lifecycle->execute($this, 'rollback', false);
        return $code ? $code : 1;
    }

    private function dispatchEvent($event) {
        try {
            $this->injector->get('Scoop\Event\Dispatcher')->dispatch($event);
        } catch (\Exception $ex) {
            error_log($ex);
        } catch (\Throwable $ex) {
            error_log($ex);
        }
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
}
