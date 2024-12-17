<?php

namespace Scoop\Persistence\Entity\Type;

class Numeric
{
    public function disassemble($value)
    {
        return floatval($value);
    }

    public function assemble($value)
    {
        return floatval($value);
    }

    public function comparate($oldValue, $newValue)
    {
        return floatval($oldValue) === $newValue;
    }
}
