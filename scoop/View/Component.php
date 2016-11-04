<?php
namespace Scoop\View;

/**
 * Interface necesaria para el registro de componentes de la vista.
 */
interface Component
{
	/**
	 * Genera la estructura del componente a ser mostrado en la vista.
	 * @return string Estructura del componente.
	 */
	public function render();
}
