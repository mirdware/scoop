<?php
namespace App\Models;

use Scoop\Persistence\Driver\DBC;

class Usuario implements \Scoop\Model
{
    private $con;
    private $index;
    
    public function __construct($nombre = false)
    {
        $this->con = DBC::get();
        $this->index = ($nombre)? ' WHERE nom_usuario = '.$this->con->escape($nombre): '';
    }
    
    public static function exist($key, $value)
    {
        if ($key != 'nom_usuario' && $key != 'email') {
             throw new Exception('exist solo premite el uso de "nom_usuario" o "email" como key, ud a usado "'.$key.'"');
        }
        $con = DBC::get();
        $res = $con->query ('SELECT nom_usuario FROM usuario WHERE '.$key.' = '.$con->escape($value));
        if ($res->numRows() == 1) {
            return new Usuario ($res->result(0));
        }
    }
    
    public static function create($array)
    {
        $con = DBC::get();
        $name = 'INSERT INTO usuario ( ';
        $value = ') VALUES ( ';
        if (isset($array['clave'])) {
            $name .= 'clave';
            $value .= 'md5('.$con->escape($array['clave']).'), ';
            unset($array['clave']);
        }
        foreach($array as $key => $val) {
            $name .= $key.', ';
            $value .= $con->escape($val).', ';
        }
        $con->query(substr($name, 0, -2).substr($value, 0, -2).')');
        return new Usuario ($array['nom_usuario']);
    }
    
    public function read($data=array())
    {
        $query = 'SELECT ';
        if ( !empty($data) ) {
            $query .= implode ($data, ', ');
        } else {
            $query .= 'nom_usuario, email';
        }
        return $this->con->query($query.' FROM usuario'.$this->index);
    }
    
    public function update($array)
    {
        $query = 'UPDATE usuario SET ';
        if (isset($array['clave'])) {
            $name .= 'clave = md5('.$this->con->escape($array['clave']).'), ';
            unset($array['clave']);
        }
        foreach($array as $key => $val) {
            $query .= $key.' = '.$this->con->escape($val).', ';
        }
        $this->con->query(substr($query, 0, -2).$this->index);
        return $this->con->error();
    }
    
    public function delete()
    {
        $this->con->query ('DELETE FROM usuario'.$this->index);
        return $this->con->error();
    }
}
