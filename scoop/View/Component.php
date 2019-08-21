<?php
namespace Scoop\View;

interface Component
{
	/**
	 * Genera la estructura del componente a ser mostrado en la vista.
	 * @return string Estructura del componente.
	 */
	public function render();
}
