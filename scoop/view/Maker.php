<?php
namespace scoop\view;

use scoop\View as View;

abstract class Maker {
	private static $footer = '';

	public static function expand ( $parent, &$view ) {
		extract( $view->getData() );
		ob_start();
		require View::ROOT_VIEWS.$parent.View::EXT_VIEWS;
		self::$footer = ob_get_contents();
		ob_clean();
	}

	public static function output () {
		ob_start( array('\scoop\view\Maker', 'publish') );
	}

	private static function publish ($buffer) {
		return $buffer.self::$footer;
	}
}