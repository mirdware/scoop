<?php
namespace Scoop\View;

abstract class Heritage {
	private static $footer;
	private static $firstView;
	private static $data;

	public static function init($data)
	{
		self::$footer = '';
		self::$firstView = TRUE;
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

	public static function output()
	{
		$fun = NULL;
		if (self::$firstView) {
			$fun = function ($buffer) {
				$buffer .= self::$footer;
				return $buffer;
			};
			self::$firstView = FALSE;
		}
		ob_start($fun);
	}
}
