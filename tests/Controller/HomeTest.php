<?php

namespace App\Test\Controller;

use App\Controller\Home;
use App\Repository\QuoteArray;
use PHPUnit\Framework\TestCase;
use Scoop\View;

class HomeTest extends TestCase
{
    public function testShowViewQuote()
    {
        $repository = new QuoteArray();
        $home = new Home($repository);
        $view = $home->get();
        $this->assertInstanceOf(View::class, $view);
    }
}
