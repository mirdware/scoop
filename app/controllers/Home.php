<?php
namespace App\Controllers;

class Home extends \Scoop\Controller
{
	private $view;

	public function __construct()
	{
		$this->view = new \Scoop\View('home');
		$this->view->set(array(
			'title' => \Scoop\Bootstrap\Config::get('app.name'),
			'ajax' => $this->ajax()
		));
	}

	public function main()
	{
		$this->view->render();
	}
}
