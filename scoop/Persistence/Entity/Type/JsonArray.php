<?php

namespace Scoop\Persistence\Entity\Type;

class JsonArray extends Json
{
    public function assemble($value)
    {
        return json_decode($value, true);
    }
}
