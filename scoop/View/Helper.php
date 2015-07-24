<?php
namespace Scoop\View;

use \Scoop\Bootstrap\Config as Config;

abstract class Helper
{
    private static $view;
    private static $assets;

    public static function init($array)
    {
        self::$view = &$array;
        self::$assets = (array) Config::get('asset') + array(
            'path' => 'public/',
            'img' => 'images/',
            'css' => 'css/',
            'js' => 'js/'
        );
    }

    public static function get($key)
    {
        if (isset(self::$view[$key])) {
            return self::$view[$key];
        }
        return Config::get($key);
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
