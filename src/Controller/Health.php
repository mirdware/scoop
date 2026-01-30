<?php

namespace App\Controller;

use Scoop\Http\Message\Server\Request;

class Health
{
    /**
     * @return array<string, string>
     */
    public function get()
    {
        return ['status' => 'ok'];
    }
}
