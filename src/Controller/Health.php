<?php

namespace App\Controller;

class Health
{
    /**
     * @return array<string, string>
     */
    public function get()
    {
        return array('status' => 'ok');
    }
}
