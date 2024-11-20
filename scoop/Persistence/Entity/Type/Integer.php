<?php

namespace Scoop\Persistence\Entity\Type;

class Integer
{
    public function disassemble($value)
    {
        return intval($value);
    }

    public function assemble($value)
    {
        return intval($value);
    }
}
