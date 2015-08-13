<?php

class HomeTest extends \PHPUnit_Framework_TestCase
{
    public function testHomeReturnView()
    {
        $quoteTest = new \App\Repository\QuoteArray();
        $home = new \Controller\Home($quoteTest);
        $view = $home->get(array());
        $this->assertInstanceOf('\Scoop\View', $view);
    }
}