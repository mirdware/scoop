<?php

namespace Scoop\Persistence\Entity\Type;

class Varchar
{
    public function disassemble($value)
    {
        return trim($value);
    }

    public function assemble($value)
    {
        return trim($value);
    }
}
