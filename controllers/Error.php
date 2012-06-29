<?php
class Error extends Controller {
	
	function __construct() {
		header('HTTP/1.0 404 Not Found');
		$this->setView('title', 'ERROR 404!');
	}
	
	public static function main() {
		$error = new Error;
		$error->render('404');
	}
}
?>