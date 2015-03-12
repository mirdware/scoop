<?php
namespace Scoop\View;

use \Scoop\Bootstrap\Config as Config;

abstract class Helper
{
    private static $view;

    public static function init($array)
    {
        self::$view =& $array;
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
        return ROOT.Config::get('path.public').$resource;
    }

    public static function img($image)
    {
        return self::overt(Config::get('path.img').$image);
    }

    public static function css($styleSheet)
    {
        return self::overt(Config::get('path.css').$styleSheet);
    }

    public static function js($javaScript)
    {
        return self::overt(Config::get('path.js').$javaScript);
    }

}
