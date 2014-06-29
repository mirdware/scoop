<?php
namespace app\controllers;

class Home extends \scoop\Controller {
	private $view;
	
	public function __construct () {
		$this->view = new \scoop\View( 'home' );
		$this->view->set(array(
			'title' => 'welcome',
			'ajax' => $this->ajax()
		));
	}
	
	public function main () {
		$this->view->render();
	}
}