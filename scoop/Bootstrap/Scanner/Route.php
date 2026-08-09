<?php

namespace Scoop\Bootstrap\Scanner;

class Route extends \Scoop\Bootstrap\Scanner
{
    public function __construct(\Scoop\Bootstrap\Environment $environment)
    {
        parent::__construct(
            $environment->getConfig('routes', 'app/routes'),
            '/(endpoint|middlewares|default)\.php$/',
            array('routes' => $environment->getStoragePath('cache') . 'routes.php'),
            $environment->getStoragePath('cache') . 'routes.meta.php'
        );
    }

    protected function build($fileMap)
    {
        $map = array();
        $middlewaresMap = array();
        $tree = array('s' => array(), 'd' => null);
        uksort($fileMap, function($a, $b) {
            $depthA = substr_count($a, '/');
            $depthB = substr_count($b, '/');
            if ($depthA !== $depthB) {
                return $depthA - $depthB;
            }
            return strcmp(basename($b), basename($a));
        });
        foreach ($fileMap as $route) {
            if (isset($route['id'])) {
                $id = $route['id'];
                if (isset($map[$id])) {
                    throw new \RuntimeException("Duplicate route ID '$id' found in [{$map[$id]['url']}, {$route['url']}]");
                }
                $applicableMiddlewares = array();
                foreach ($middlewaresMap as $url => $middlewares) {
                    if ($route['url'] === $url || strpos($route['url'], rtrim($url, '/') . '/') === 0) {
                        $applicableMiddlewares = array_merge($applicableMiddlewares, $middlewares);
                    }
                }
                $applicableMiddlewares = array_merge($applicableMiddlewares, $route['middlewares']);
                $this->insert($tree, $route['url'], 'id', $id);
                $map[$id] = array(
                    'url' => $route['url'],
                    'controller' => $route['controller'],
                    'validator' => $route['validator'],
                    'middlewares' => array_unique($applicableMiddlewares),
                );
            } elseif (key_exists('value', $route)) {
                $this->insert($tree, $route['url'], '_', array(
                    'v' => $route['value'],
                    'm' => $route['match']
                ));
            } else {
                $middlewaresMap[$route['url']] = $route['middlewares'];
            }
        }
        return array('routes' => compact('map', 'tree'));
    }

    protected function check($filePath)
    {
        $file = basename($filePath);
        $route = include $filePath;
        $url = '/' . str_replace(array($this->getDirectory(), $file), '', $filePath);
        if ($file === 'endpoint.php') {
            if (!is_array($route) || !isset($route['controller'])) {
                throw new \RuntimeException("Invalid route definition in file '$filePath'");
            }
            return array(
                'url' => $url,
                'id' => isset($route['id']) ? $route['id'] : uniqid(),
                'controller' => $route['controller'],
                'validator' => isset($route['validator']) ? $route['validator'] : null,
                'middlewares' => isset($route['middlewares']) ? $this->validateMiddlewares($route['middlewares'], $filePath) : array()
            );
        }
        if ($file === 'middlewares.php') {
            return array(
                'url' => $url,
                'middlewares' => $this->validateMiddlewares($route, $filePath)
            );
        }
        return array(
            'url' => $url,
            'value' => isset($route['value']) ? $route['value'] : $route,
            'match' => isset($route['match']) ? $route['match'] : array()
        );
    }

    private function insert(&$node, $url, $key, $value)
    {
        $path = trim($url, '/');
        $segments = $path === '' ? array() : explode('/', $path);
        foreach ($segments as $segment) {
            if (strpos($segment, '[') === 0) {
                if (!isset($node['d'])) {
                    $node['d'] = array('s' => array(), 'd' => null, 'p' => substr($segment, 1, -1));
                }
                $node = &$node['d'];
            } else {
                if (!isset($node['s'][$segment])) {
                    $node['s'][$segment] = array('s' => array(), 'd' => null);
                }
                $node = &$node['s'][$segment];
            }
        }
        $node[$key] = $value;
    }

    private function validateMiddlewares($middlewares, $filePath)
    {
        if (!is_array($middlewares) || array_keys($middlewares) !== range(0, count($middlewares) - 1)) {
            throw new \RuntimeException("Invalid middlewares definition in file '$filePath'");
        }
        return $middlewares;
    }
}
