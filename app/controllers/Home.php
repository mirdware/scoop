<?php
namespace Controller;

class Home extends \Scoop\Controller
{
    private $quotes;

    public function __construct(\App\Repository\Quote $quote)
    {
        $this->quotes = $quote->publish();
    }

    public function get(array $args)
    {
        if ($args) {
            $this->notFound();
        }
        $view = new \Scoop\View('home');
        $index = rand(0, count($this->quotes)-1);
        return $view->set('title', 'MirdWare')
                    ->set($this->quotes[$index]);
    }
}
