<?php
namespace Scoop\Bootstrap;

class Configuration
{
    private $config = array();
    private static $sessionInit = false;

    public function __construct($fileName)
    {
        if (!self::$sessionInit) {
            self::$sessionInit = session_start();
        }
        $this->add($fileName);
    }

    public function get($name)
    {
        $name = explode('.', $name);
        $res = $this->config;
        foreach ($name as $key) {
            if (!isset($res[$key])) {
                return false;
            }
            $res = $res[$key];
        }
        return $res;
    }

    public function add($fileName)
    {
        $this->config += require $fileName.'.php';
    }
}
