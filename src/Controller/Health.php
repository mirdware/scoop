<?php

namespace App\Controller;

use Scoop\Http\Message\Response;

class Health
{
    /**
     * @return Response
     */
    public function get()
    {
        return new Response(
            200,
            ['Content-Type' => 'application/json'],
            ['status' => 'ok']
        );
    }
}
