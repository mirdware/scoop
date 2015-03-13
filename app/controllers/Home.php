<?php
namespace Controller;

class Home extends \Scoop\Controller
{
    private $view;

    public function __construct()
    {
        $this->view = new \Scoop\View('home');
        $this->view->set(array(
            'title' => 'MirdWare'
        ));
    }

    public function get(array $args)
    {
        if ($args) {
            $this->notFound();
        }
        return $this->view;
    }
}
