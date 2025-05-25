<?php

namespace Scoop\Bootstrap;

class Environment
{
    private static $sessionInit = false;
    private static $loaders = array(
        'import' => 'Scoop\Bootstrap\Loader\Importer',
        'json' => 'Scoop\Bootstrap\Loader\JsonParser',
        'instanceof' => 'Scoop\Bootstrap\Loader\TypeMapper'
    );
    private static $version;
    private $config;

    public function __construct($configPath)
    {
        if (!self::$sessionInit) {
            self::$sessionInit = session_start();
        }
        $this->config = array(
            'base' => require $configPath . '.php',
            'data' => array()
        );
        if (isset($_SERVER['HTTP_HOST'])) {
            $http = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
            (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') ||
            (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] == 'on') ? 'https:' : 'http:';
            $viteHost = getenv('VITE_HOST');
            define('ROOT', $viteHost ? $viteHost : $http . '//' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . '/');
        }
        define('DEBUG_MODE', filter_var(ini_get('display_errors'), FILTER_VALIDATE_BOOLEAN));
        self::$loaders += $this->getConfig('loaders', array());
    }

    public function getConfig($name, $default = null)
    {
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
                if ($key === 'providers') {
                    throw new \UnexpectedValueException('it is not possible to perform lazy loading on providers');
                }
                $res[$key] = $this->loadLazily($res[$key]);
            }
            $res = $res[$key];
        }
        $this->config['data'][$name] = $res;
        return $res;
    }

    public function loadLazily($path)
    {
        $index = strpos($path, ':');
        if ($index !== -1) {
            $method = substr($path, 0, $index);
            if (isset(self::$loaders[$method])) {
                $url = substr($path, $index + 1);
                $loader = \Scoop\Context::inject(self::$loaders[$method]);
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
}
