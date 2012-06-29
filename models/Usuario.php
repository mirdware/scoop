<?php
class Usuario implements Model {
	private $con;
	private $index;
	
	public function __construct ($index = false) {
		$this->con = Conexion::get();
		if ($index) {
			$this->index = ' WHERE nombre = '.$this->con->escape($index);
		} else {
			$this->index = '';
		}
	}
	
	public static function exist($index) {
		$con = Conexion::get();
		return $con->query ('SELECT COUNT(*) FROM usuario WHERE nombre = '.$con->escape($index))->result(0);
	}
	
	public static function create($array){
		$con = Conexion::get();
		$name = 'INSERT INTO usuario ( ';
		$value = ') VALUES ( ';
		foreach($array as $key => $val) {
			$name .= $key.', ';
			$value .= $con->escape($val).', ';
		}
		$con->query(substr($name, 0, -2).substr($value, 0, -2).')');
		return new Usuario ($array['nombre']);
	}
	
	public function read($data=array()){
		$query = 'SELECT ';
		if ( !empty($data) ) {
			for ($i=0, $size=count($data); $i<$size; $i++) {
				$query .= $data[$i].', ';
			}
			$query = substr ($query, 0, -2);
		} else {
			$query .= 'nombre, nombres, apellidos, direccion';
		}
		return $this->con->query($query.' FROM usuario'.$this->index);
	}
	
	public function update($array){
		$query = 'UPDATE cliente SET ';
		foreach($array as $key => $val) {
			$query .= $key.' = ';
			$query .= $this->con->escape($val).', ';
		}
		$this->con->query(substr($query, 0, -2).$this->index);
		return $this->con->error();
	}
	
	public function delete(){
		$this->query ('DELETE FROM cliente'.$this->index);
		return $this->con->error();
	}
}
?>