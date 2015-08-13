<?php
use \App\Repository\QuoteMongo;

class QuoteMongoTest extends \PHPUnit_Framework_TestCase
{
    public function testPublishArray()
    {
        $quote = new QuoteMongo();
        $quotes = $quote->publish();
        $this->assertInternalType('array', $quotes);
    }
}
