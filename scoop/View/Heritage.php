<?php
namespace Scoop\View;

abstract class Heritage {
    private static $footer;
    private static $data;

    public static function init($data)
    {
        self::$footer = '';
        self::$data = $data;
    }

    public static function expand($parent)
    {
        Template::parse($parent);
        extract(self::$data);
        ob_start();
        require \Scoop\View::ROOT.$parent.\Scoop\View::EXT;
        self::$footer = trim(ob_get_contents()).self::$footer;
        ob_clean();
    }

    public static function beam()
    {
        ob_start();
    }

    public static function getFooter()
    {
    	return self::$footer;
    }
}
