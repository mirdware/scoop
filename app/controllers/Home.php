<?php
namespace Controller;

class Home extends \Scoop\Controller
{
    private $message;

    public function __construct(\App\Repository\Message $message)
    {
        $this->message = $message;
    }

    public function get(array $args)
    {
        if ($args) {
            $this->notFound();
        }
        $view = new \Scoop\View('home');
        return $view->set('title', 'MirdWare')
                    ->set($this->message->publish());
    }
}
