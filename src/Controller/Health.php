<?php

namespace App\Controller;

use Scoop\Validation\Rule\Required;
use Scoop\Validator;

class Health
{
    /**
     * @return array<string, string>
     */
    public function get()
    {
        return array('status' => 'ok');
    }

    public function post(\Scoop\Http\Message\Server\Request $request)
    {
        return $request->getParsedBody();
    }

    public function put(\Scoop\Http\Message\Server\Request $request, $id)
    {
        $validator = new Validator();
        $validator->add('name', new Required)
        ->add('email', new Required)
        ->add('phone', new Required)
        ->add('address', new Required);
        $command = $request->get('\App\Controller\Command')
        ->with(compact('id'))
        ->fromBody($validator);
        var_dump($command);
        return $command;
    }
}
