<?php
namespace App\Repository;

class QuoteMongo implements Quote
{
    public function publish()
    {
        $mongo = new \MongoClient();
        $collection = $mongo->scoop->quotes;
        return iterator_to_array($collection->find(), false);
    }
}
