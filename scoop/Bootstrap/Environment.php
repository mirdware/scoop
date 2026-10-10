<?php

namespace Scoop\Bootstrap;

/**
 * @deprecated Removed instead of \Scoop\Context
 * @since 0.8.5
 * @see \Scoop\Context
 */
class Environment
{
    private static $sessionInit = false;
    private static $loaders = array(
        'import' => 'Scoop\Bootstrap\Loader\Importer',
        'typeof' => 'Scoop\Bootstrap\Loader\TypeMapper',
        'instanceof' => 'Scoop\Bootstrap\Loader\TypeInstantiator',
        'json' => 'Scoop\Bootstrap\Loader\Factory\JsonParser:create'
    );
    private static $version;
    private $injector;
    private $config;
    private $storagePath;

    public function __construct($context)
    {
        if (!$context['stateless'] && !self::$sessionInit) {
            self::$sessionInit = session_start();
        }
        $this->config = $context['config'] . '.php';
        $this->storagePath = $context['storage'];
        if (isset($_SERVER['HTTP_HOST'])) {
            $http = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
            (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') ||
            (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] == 'on') ? 'https:' : 'http:';
            $viteHost = getenv('VITE_HOST');
            define('ROOT', $viteHost ? $viteHost : $http . '//' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . '/');
        }
        define('DEBUG_MODE', filter_var(ini_get('display_errors'), FILTER_VALIDATE_BOOLEAN));
        $this->injector = $this->instantiateInjector();
    }

    public function getConfig($name, $default = null)
    {
        if (is_string($this->config)) {
            $this->config = array(
                'base' => require $this->config,
                'data' => array()
            );
            if (!isset($this->config['base']['providers']) || !is_array($this->config['base']['providers'])) {
                throw new \UnexpectedValueException('it is not possible to perform lazy loading on providers');
            }
            self::$loaders += $this->getConfig('loaders', array());
        }
        if (isset($this->config['data'][$name])) {
            return $this->config['data'][$name];
        }
        $data = explode('.', $name);
        $res = $this->config['base'];
        foreach ($data as $key) {
            if (!isset($res[$key])) {
                return $default;
            }
            if (is_string($res[$key])) {
                $res[$key] = $this->loadLazily($res[$key]);
            }
            $res = $res[$key];
        }
        $this->config['data'][$name] = $res;
        return $res;
    }

    public function getStoragePath($path)
    {
        $path = trim($this->storagePath, '/') . '/' . trim($path, '/') . '/';
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
        return $path;
    }

    public function getInjector()
    {
        return $this->injector;
    }

    public function loadLazily($path)
    {
        $index = strpos($path, ':');
        if ($index !== false) {
            $method = substr($path, 0, $index);
            if (isset(self::$loaders[$method])) {
                $url = substr($path, $index + 1);
                $loader = $this->injector->get(self::$loaders[$method]);
                return $loader->load($url);
            }
        }
        return $path;
    }

    public function getVersion()
    {
        if (!self::$version) {
            $index = file_get_contents('index.php');
            preg_match_all('# @version\s+(.*?)\n#s', $index, $annotations);
            self::$version = $annotations[1][0];
        }
        return self::$version;
    }

    private function instantiateInjector()
    {
        $injector = $this->getConfig('injector', '\Scoop\Container\Injector\Memory');
        $baseInjector = '\Scoop\Container\Injector';
        $injector = new $injector($this);
        if (!($injector instanceof $baseInjector)) {
            throw new \UnexpectedValueException("$injector not implement $baseInjector");
        }
        return $injector;
    }
}
