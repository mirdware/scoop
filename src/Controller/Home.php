<?php
namespace App\Controller;

use \Scoop\Controller;
use \Scoop\View;
use \App\Repository\Quote;

class Home extends Controller
{
    private $quotes;

    public function __construct(Quote $quote)
    {
        $this->quotes = $quote->publish();
    }

    public function get()
    {
        $view = new View('home');
        $index = rand(0, count($this->quotes) - 1);
        return $view->set('title', 'MirdWare')
        ->set($this->quotes[$index]);
    }
}
