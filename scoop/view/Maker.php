<?php
namespace scoop\view;

use scoop\View as View;

abstract class Maker {
	private static $footer = '';
	private static $firstView = TRUE;

	public static function expand ( $parent ) {
		Template::parse( $parent );
		extract( \scoop\view\Wrapper::get('data') );
		ob_start();
		require View::ROOT.$parent.View::EXT;
		self::$footer = trim( ob_get_contents() ).self::$footer;
		ob_clean();
	}

	public static function output () {
		$fun = NULL;
		if (self::$firstView) {
			$fun = function ($buffer) {
				$buffer .= self::$footer;
				self::$footer = '';
				return $buffer;
			};
			self::$firstView = FALSE;
		}
		ob_start( $fun );
	}
}