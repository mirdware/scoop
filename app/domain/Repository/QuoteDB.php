<?php
namespace App\Repository;

class QuoteDB implements Quote
{
    public function publish()
    {
        $sqo = new \Scoop\Storage\SQO('quotes');
        return $sqo->read('quote', 'author')
                    ->run()->fetchAll();
    }
}
