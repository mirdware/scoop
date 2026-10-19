<?php

namespace Scoop;

class Clock
{
    public function now()
    {
        return \DateTime::createFromFormat('U.u', sprintf('%.6F', microtime(true)));
    }
}
