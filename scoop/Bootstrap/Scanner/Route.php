<?php

namespace Scoop\Bootstrap\Scanner;

class Route extends \Scoop\Bootstrap\Scanner
{
    public function __construct($directory) {
        $cacheFilePath = $this->getPath('/cache/', 'routes.index.php');
        $metaFilePath = $this->getPath('/cache/', 'routes.meta.php');
        parent::__construct($directory, '/endpoint\.php$/', $cacheFilePath, $metaFilePath);
    }

    protected function buildMap()
    {
        $map = array();
        foreach ($this->map as $filePath => $metaInfo) {
            if ($metaInfo['id'] !== null) {
                $map[$metaInfo['id']] = '/' . str_replace(
                    array($this->directory, 'endpoint.php'),
                    '',
                    $filePath
                 );
            }
        }
        return $map;
    }

    protected function checkFile($filePath) {
        $route = include $filePath;
        if (!is_array($route) || !isset($route['controller'])) {
            throw new \RuntimeException("Invalid route definition in file '$filePath'");
        }
        $id = isset($route['id']) ? $route['id'] : null;
        if ($id !== null && isset($this->routesMap[$id])) {
            throw new \RuntimeException("Duplicate id found: '$id' in file '$filePath'");
        }
        return array('id' => $id);
    }
}
