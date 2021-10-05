<?php
namespace App\Controller;

use Scoop\Controller;

class Pageable extends Controller
{
    public function get($name) {
        $pag = $this->getRequest()->getQuery($name);
        $data = [
            'size' => 5,
            'total' => 15,
            'page' => intval($pag),
            'result' => ['Uno', 'Dos', 'Tres', 'Cuatro', 'Cinco']
        ];
        switch($data['page']) {
            case 1:
                $data['result'] = ['Seis', 'Siete', 'Ocho', 'Nueve', 'Diez'];
                break;
            case 2:
                $data['result'] = ['Once', 'Doce', 'Trece', 'Catorce', 'Quince'];
                break;
        }
        return $data;
    }
}