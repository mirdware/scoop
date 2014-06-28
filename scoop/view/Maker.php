<?php
namespace scoop\view;

use scoop\View as View;

abstract class Maker {
	private static $footer = '';

	public static function expand ( $parent, &$view ) {
		extract( $view->getData() );
		ob_start();
		require View::ROOT.$parent.View::EXT;
		self::$footer = ob_get_contents();
		ob_clean();
	}

	public static function output () {
		ob_start( function ($buffer) {
			return $buffer.self::$footer;
		} );
	}
}