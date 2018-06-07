<?php
class modalidad extends concodigo {
	const NOMBRE_TABLA = 'accidente_modalidad';
	const NOMBRE_TABLA_COMPLETO = TABLE_MODALIDAD;
	public function __construct($param, $extra = false) {
		parent::instance($param,$extra);
	}
}
