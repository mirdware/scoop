<?php

namespace Scoop\Persistence\Entity\Type;

class Serial extends Integer
{
    public function isAutoincremental()
    {
        return true;
    }
}
