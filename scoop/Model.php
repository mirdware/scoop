<?php
namespace Scoop;
/*
    Interfaz del Modelo, trabaja sobre una filosofia CRUD-E
        Create: Generar objetos del modelo, insertandolos en la base de datos.
        Read: Obtiene de la base de datos los atributos que le son pasados en el array.
        Update: Actualiza la base de datos segun la información contenida en el array asociativo.
        Delete: Elimina el objeto de la base de datos.
*/
interface Model
{
    public static function getFilter();
    public static function get($single);
    public function fromArray($array);
    public function getPK();
    public function persist();
    public function remove();
}
