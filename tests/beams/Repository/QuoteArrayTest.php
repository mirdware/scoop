<?php
use \App\Repository\QuoteArray;

class QuoteArrayTest extends \PHPUnit_Framework_TestCase
{
    public function testPublishArray()
    {
        $quote = new QuoteArray();
        $quotes = $quote->publish();
        $this->assertInternalType('array', $quotes);
    }
}
