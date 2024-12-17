<?php

namespace Scoop\Persistence\Entity\Type;

class Json
{
    public function disassemble($value)
    {
        return json_encode($value);
    }

    public function assemble($value)
    {
        return json_decode($value);
    }
}
