<?php
namespace app\controllers;

class Home extends \scoop\Controller {
	private $view;
	
	public function __construct () {
		$this->view = new \scoop\View( 'home' );
		$this->view->set(array(
			'title' => 'bootstrap '.APP_NAME.' - MirdWare',
			'ajax' => $this->ajax(),
			'app_name' => APP_NAME
		));
	}
	
	public function main () {
		$this->view->render();
	}
}