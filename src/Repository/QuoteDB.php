<?php
namespace App\Repository;

use \Scoop\Storage\SQO;

class QuoteDB implements Quote
{
    public function publish()
    {
        $sqo = new SQO('quotes');
        return $sqo->read('quote', 'author')
                    ->run()->fetchAll();
    }
}
