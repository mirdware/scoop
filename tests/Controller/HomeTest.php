<?php

namespace App\Test\Controller;

use Scoop\View;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;
use App\Controller\Home;
use App\Repository\QuoteArray;

#[CoversMethod(Home::class, 'get')]
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
