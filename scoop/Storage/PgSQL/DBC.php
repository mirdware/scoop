<?php
namespace Scoop\Storage\PgSQL;

class DBC
{
    //conexion persistente a la base de datos
    private $conex;
    //Lista de conexiones existentes en la ejecución de la aplicación
    private static $instances = array();
    const FETCH_ASSOC = 1;
    const FETCH_BOTH = 2;
    const FETCH_NUM = 3;
    const FETCH_OBJ = 4;

    /*constructor*/
    private function __construct($db, $user, $pass, $host)
    {
        $this->conex = pg_connect(
            'host='.$host.' port=5432 dbname='.$db.
            ' user='.$user.' password='.$pass
        ) or die();
        $this->query('SET NAMES \'utf8\';BEGIN');
    }

    public function __destruct()
    {
        if ($this->conex) {
            $this->query('COMMIT');
            pg_close($this->conex);
        }
    }

    /*Inpedir el clonado de objetos*/
    private function __clone() {}

    /*Patron Multiton*/
    public static function get($conf = null))
    {
        $bundle = 'db.default';
        if (is_string($conf)) {
            $bundle = $conf;
        }
        $config = \Scoop\Bootstrap\Config::get($bundle);
        if ( is_array($conf) ) {
            $config += $conf;
        }
        $key = implode('', $config);

        if (!isset(self::$instances[$key])) {
            self::$instances[$key] = new DBCpgSQL(
                $config['database'],
                $config['user'],
                $config['password'],
                $config['host']
            );
        }
        return self::$instances[$key];
    }

    /*abstraccion de los metodos independiente del DBMS*/
    public function query($consulta)
    {
        if(!$this->conex) {
            return false;
        }

        $consulta = trim($consulta);
        //echo $consulta;
        $r = pg_query($this->conex, $consulta);
        if ( !$r ) {
            throw new \Exception($this->error(), 1);
        }

        if(strpos(strtoupper($consulta), 'SELECT') === 0) {
            $res = new __Result__($r);
            return $res;
        } else {
            return $r;
        }
    }

    public function error()
    {
        return pg_last_error($this->conex);
    }

    public function quote($val)
    {
        $val = trim($val);
        if ($val === null || $val === '') {
            return 'null';
        }
        if (get_magic_quotes_gpc()) {
            $val = stripslashes($val);
        }
        $val = "'" . pg_escape_string($val) . "'";
        return $val;
    }

    public function lastId()
    {
        return $this->query('SELECT lastval()')->result(0);
    }

}

//**********************************************************************************

final class __Result__ {
    private $res;

    public function __construct($res)
    {
        $this->res = $res;
    }

    public function __destruct()
    {
        if($this->res) {
            pg_free_result($this->res);
        }
    }

    /*abstraccion de los metodos independiente del DBMS*/
    public function numRows()
    {
        return pg_num_rows($this->res);
    }

    public function toObject()
    {
        return pg_fetch_object($this->res);
    }

    public function toArray()
    {
        return pg_fetch_array($this->res);
    }

    public function toAssoc()
    {
        return pg_fetch_assoc($this->res);
    }

    public function toRow()
    {
        return pg_fetch_row($this->res);
    }

    public function result($row=0, $field=0)
    {
        return pg_fetch_result($this->res,$row, $field);
    }

    public function reset()
    {
        pg_result_seek($this->res, 0);
    }
}
