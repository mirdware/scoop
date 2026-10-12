<?php

namespace Scoop\Bootstrap;

class Application
{
    private static $loader;
    private $context;
    private $injector;
    private $stateful;
    private $error;
    private $status;
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
        $this->context = new \Scoop\Context($options);
        $this->injector = $this->context->getInjector();
    }

    public function run()
    {
        $this->bootstrap();
        $requestType = $this->context->getConfig('request', 'Scoop\Http\Message\Server\Request');
        $router = $this->inject('Scoop\Http\Router');
        $request = $this->inject($requestType);
        $this->inject('Scoop\Bootstrap\Configuration')->setUp();
        \Scoop\View\Service::setUp($this->context);
        try {
            $response = $router->route($request);
            $this->executeDependency('Scoop\Persistence\Entity\Manager', 'flush');
            $this->executeDependency('Scoop\Persistence\Connection', 'commit');
        } catch (\Exception $ex) {
            $response = $this->manageError($ex, $request->isAjax());
        } catch (\Throwable $ex) {
            $response = $this->manageError($ex, $request->isAjax());
        }
        $this->printResponse($response);
        $this->terminated = true;
    }

    public function terminate()
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        try {
            if ($this->terminated) {
                $this->inject('Scoop\Event\Dispatcher')->dispatch(
                    new \Scoop\Http\Event\RequestFinished($this->error, $this->status)
                );
            }
        } catch (\Exception $ex) {
            error_log($ex);
        } catch (\Throwable $ex) {
            error_log($ex);
        }
        \Scoop\View\Service::reset();
        $this->executeDependency('Scoop\Persistence\Entity\Manager', 'clean');
        $this->executeDependency('Scoop\Log\Logger', 'flush');
        $this->injector->clean();
        $this->terminated = false;
    }

    public function inject($id)
    {
        return $this->injector->get($id);
    }

    private function manageError($ex, $isAjax)
    {
        $this->executeDependency('Scoop\Persistence\Connection', 'rollback');
        $exceptionManager = $this->inject('Scoop\Http\Error\Mapper');
        $this->status = $exceptionManager->getStatusCode($ex);
        $this->error = $ex;
        if ($ex instanceof \Scoop\Http\Exception\Unprocessable) {
            return $ex->getResponse();
        }
        if ($this->status) {
            return $exceptionManager->map($ex, $isAjax, $this->status);
        }
        $this->terminated = true;
        throw $ex;
    }

    private function executeDependency($className, $callback)
    {
        $method = 'contains';
        if (!is_callable(array($this->injector, $method)) || $this->injector->$method($className)) {
            $this->inject($className)->$callback();
        }
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

    private function bootstrap()
    {
        $this->terminated = false;
        $this->error = null;
        $this->status = null;
        if (!defined('ROOT')) {
            $http = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
            (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') ||
            (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] == 'on') ? 'https:' : 'http:';
            $viteHost = getenv('VITE_HOST');
            define('ROOT', $viteHost ? $viteHost : $http . '//' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . '/');
        }
        if ($this->stateful && session_status() === PHP_SESSION_NONE) {
            session_start();
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
