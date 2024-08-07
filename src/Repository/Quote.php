<?php

namespace App\Repository;

interface Quote
{
    /**
     * @return array<array<string, string>>
     */
    public function publish();
}
