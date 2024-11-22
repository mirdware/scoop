<?php

namespace Scoop\Persistence\Entity\Type;

class Boolean
{
    public function disassemble($value)
    {
        return $value ? 1 : 0;
    }

    public function assemble($value)
    {
        return !!$value;
    }
}
