<?php
namespace Controller;

class Home extends \Scoop\Controller
{
    public function hola()
    {
        return '';
    }
    
    public function get(array $args)
    {
        if ($args) {
            $this->notFound();
        }
        $view = new \Scoop\View('home');
        return $view->set('title', 'MirdWare');
    }
}
