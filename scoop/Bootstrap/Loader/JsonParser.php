<?php

namespace Scoop\Bootstrap\Loader;

class JsonParser
{
    private $cachePath;

    public function __construct(\Scoop\Bootstrap\Environment $environment)
    {
        $storagePath = $environment->getConfig('storage', 'app/storage/');
        $storagePath = rtrim($storagePath, '/') . '/';
        $this->cachePath = "{$storagePath}cache/json/";
    }

    public function load($url)
    {
        $cacheFile = "{$this->cachePath}{$url}.php";
        $realPath = dirname($cacheFile);
        if (!is_dir($realPath)) {
            mkdir($realPath, 0755, true);
            return $this->getRealInfo($url);
        }
        if (!is_readable($cacheFile) || filemtime("$url.json") > filemtime($cacheFile)) {
            return $this->getRealInfo($url);
        }
        return require $cacheFile;
    }

    private function getRealInfo($url)
    {
        $json = file_get_contents("$url.json");
        if ($json === false) return array();
        $array = json_decode($json, true);
        $content = "<?php\n\nreturn " . var_export($array, true) . ";\n";
        file_put_contents("{$this->cachePath}{$url}.php", $content);
        return $array;
    }
}
