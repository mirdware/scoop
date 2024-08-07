<?php

namespace Scoop\Command;

interface Handler
{
    public function execute($command);
    public function help();
}
