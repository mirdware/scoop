<?php
namespace app\controllers;

class Error extends \scoop\Controller {
	private $view;
	
	function __construct() {
		$this->view = new \scoop\View( '404' );
		header('HTTP/1.0 404 Not Found');
		$this->view->set('title', 'ERROR 404!');
	}
	
	public function main() {
		$this->view->render();
	}
}