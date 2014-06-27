<?php
namespace scoop\view;

class Parse {
	public function __construct ($nameTemplate) {
		echo date ("F d Y H:i:s.", filemtime($nameTemplate));
	}
}