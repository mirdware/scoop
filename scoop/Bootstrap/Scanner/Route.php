<?php

namespace Scoop\Bootstrap\Scanner;

class Route extends \Scoop\Bootstrap\Scanner
{
    public function __construct(\Scoop\Bootstrap\Environment $environment)
    {
        parent::__construct(
            $environment->getConfig('routes', 'app/routes'),
            '/(endpoint|middlewares)\.php$/',
            $this->getPath('/cache/', 'routes.php'),
            $this->getPath('/cache/', 'routes.meta.php')
        );
    }

    protected function build($map)
    {
        uasort($map, function ($a, $b) {
            if ($a['priority'] !== $b['priority']) {
                return $a['priority'] - $b['priority'];
            }
            if ($a['holdersCount'] !== $b['holdersCount']) {
                return $a['holdersCount'] - $b['holdersCount'];
            }
            return strcmp($a['url'], $b['url']);
        });
        $routesMap = array();
        $middlewaresMap = array();
        foreach ($map as $filePath => $route) {
            if (isset($route['id'])) {
                if (isset($routesMap[$route['id']])) {
                    throw new \RuntimeException("Duplicate route ID '{$route['id']}' found in file $filePath");
                }
                $applicableMiddlewares = array();
                foreach ($middlewaresMap as $url => $middlewares) {
                    if (strpos($route['url'], $url) === 0) {
                        $applicableMiddlewares = array_merge($applicableMiddlewares, $middlewares);
                    }
                }
                if (isset($route['middlewares'])) {
                    $applicableMiddlewares = array_merge($applicableMiddlewares, $route['middlewares']);
                }
                $routesMap[$route['id']] = array(
                    'url' => $route['url'],
                    'controller' => $route['controller'],
                    'validator' => $route['validator'],
                    'middlewares' => array_unique($applicableMiddlewares),
                );
            } else {
                $middlewaresMap[$route['url']] = $route['middlewares'];
            }
        }
        return $routesMap;
    }

    protected function check($filePath)
    {
        $file = basename($filePath);
        $url = '/' . str_replace(array($this->getDirectory(), $file), '', $filePath);
        $priority = trim($url) !== '' ? substr_count($filePath, '/') : 0;
        $route = include $filePath;
        if ($file === 'endpoint.php') {
            if (!is_array($route) || !isset($route['controller'])) {
                throw new \RuntimeException("Invalid route definition in file '$filePath'");
            }
            $content = array(
                'id' => isset($route['id']) ? $route['id'] : uniqid(),
                'controller' => $route['controller'],
                'validator' => isset($route['validator']) ? $route['validator'] : null,
            );
        } else {
            if (!is_array($route) || array_keys($route) !== range(0, count($route) - 1)) {
                throw new \RuntimeException("Invalid middlewares definition in file '$filePath'");
            }
            $content = array('middlewares' => $route);
            $priority -= 1;
        }
        return $content + array(
            'url' => $url,
            'priority' => $priority,
            'holdersCount' => preg_match_all('/\[\w+\]/', $url)
        );
    }
}
