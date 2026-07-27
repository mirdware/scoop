<?php

namespace Scoop\Bootstrap\Loader;

class JsonParser
{
    private $cachePath;

    public function __construct(\Scoop\Bootstrap\Environment $environment)
    {
        $this->cachePath = $environment->getStoragePath('cache/json');
    }

    public function load($url)
    {
        $cacheFile = "{$this->cachePath}{$url}.php";
        if (is_readable($cacheFile)) {
            $realFile = "$url.json";
            if (!is_readable($realFile) || filemtime($cacheFile) > filemtime($realFile)) {
                 return require $cacheFile;
            }
        }
        $realPath = dirname($cacheFile);
        if (!is_dir($realPath)) {
            mkdir($realPath, 0755, true);
        }
        return $this->getRealInfo($url);
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
