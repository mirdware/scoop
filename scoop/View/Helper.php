<?php
namespace Scoop\View;

abstract class Helper
{
    private static $view;
    private static $assets;

    public static function init($array)
    {
        $config = \Scoop\IoC\Service::getInstance('config');
        self::$view = &$array;
        self::$assets = (array) $config->get('asset') + array(
            'path' => 'public/',
            'img' => 'images/',
            'css' => 'css/',
            'js' => 'js/'
        );
    }

    public static function get($key)
    {
        $config = \Scoop\IoC\Service::getInstance('config');
        if (isset(self::$view[$key])) {
            return self::$view[$key];
        }
        return $config->get($key);
    }

    public static function overt($resource)
    {
        return ROOT.self::$assets['path'].$resource;
    }

    public static function img($image)
    {
        return self::overt(self::$assets['img'].$image);
    }

    public static function css($styleSheet)
    {
        return self::overt(self::$assets['css'].$styleSheet);
    }

    public static function js($javaScript)
    {
        return self::overt(self::$assets['js'].$javaScript);
    }

}
