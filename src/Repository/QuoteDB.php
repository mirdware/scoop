<?php

namespace App\Repository;

use Scoop\Persistence\Builder;

class QuoteDB implements Quote
{
    private $builder;

    public function __construct(Builder $builder)
    {
        $this->builder = $builder;
    }

    /**
     * @return array<array<string, string>>
     */
    public function publish()
    {
        return $this->builder
        ->build('quotes')
        ->read('quote', 'author')
        ->run()->fetchAll();
    }
}
