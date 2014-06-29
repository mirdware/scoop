<?php
namespace scoop\view;

abstract class Wrapper {
	private static $view;

	public static function init ($array) {
		self::$view =& $array;
	}

	public static function get ($key) {
		return self::$view[$key];
	}
}