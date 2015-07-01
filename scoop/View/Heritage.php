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

    public static function extend($parent)
    {
        Template::parse($parent);
        extract(self::$data);
        ob_start();
        require \Scoop\View::ROOT.$parent.\Scoop\View::EXT;
        self::$footer = trim(ob_get_contents()).self::$footer;
        ob_clean();
    }

    public static function includ($path)
    {
        Template::parse($path);
        include \Scoop\View::ROOT.$path.\Scoop\View::EXT;
    }

    public static function sprout()
    {
        ob_start();
    }

    public static function getFooter()
    {
        return self::$footer;
    }
}