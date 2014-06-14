<?php
namespace app\controllers;

class Home extends \scoop\Controller {
	private $view;
	
	public function __construct () {
		$this->view = new \scoop\View( 'home' );
		if ($this->ajax()) {
			$this->view->showLayer (FALSE);
		} else {
			$this->view->set(array(
				'title' => 'bootstrap '.APP_NAME.' - MirdWare',
				'app_name' => APP_NAME
			));
		}
	}
	
	public function send () {
		$error = '';
		if (empty($_POST['from'])) {
			$error .= "Falta campo de\n";
		}
		$from = $_POST['from'];
		unset($_POST['from']);
		if (empty($_POST['to'])) {
			$error .= "Falta campo para\n";
		}
		$to = $_POST['to'];
		unset($_POST['to']);
		if (empty($_POST['msj'])) {
			$error .= "Falta el mensaje\n";
		}
		$msj = $_POST['msj'];
		unset($_POST['msj']);
		if (empty($_POST['subject'])) {
			$error .= "Falta el asunto\n";
		}
		$asunto = $_POST['subject'];
		unset($_POST['subject']);
		if (!empty($error)) {
			return $error;
		}
		$_POST['attach'] = $_FILES;
		\scoop\package\Helper::sendMail ($asunto, $msj.'<br/><br/>RandomString: '.Helper::strRandom(25), $from, $to, $_POST);
		return 'Mensaje enviado';
	}

	public function msj ($msj, $type='out') {
		$this->view->setMessage(urldecode($msj), $type);
		$this->view->render();
	}

	public function test ($pass) {
		return \scoop\package\Helper::isSafePassword ($pass);
	}
	
	public function main () {
		$this->view->render();
	}
}