<?php
class partelesionada extends concodigo {
	const NOMBRE_TABLA = 'accidente_parte_lesionada';
	const NOMBRE_TABLA_COMPLETO = TABLE_PARTE_LESIONADA;
	public function __construct($param, $extra = false) {
		parent::instance($param,$extra);
	}
}
