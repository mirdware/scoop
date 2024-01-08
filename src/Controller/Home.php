<?php

namespace App\Controller;

use Scoop\Controller;
use Scoop\View;
use App\Repository\Quote;

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
        return $view->set(array(
            'meta' => array(
                'description' => 'Engine PHP for development of web applications',
                'keywords' => 'engine,PHP,web,scoop,scalar,development'
            )
        ))->set($this->quotes[$index]);
    }
}
