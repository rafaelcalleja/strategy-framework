<?php
class mutua extends concodigo {
	const NOMBRE_TABLA = 'accidente_mutua';
	const NOMBRE_TABLA_COMPLETO = TABLE_MUTUA;
	public function __construct($param, $extra = false) {
		parent::instance($param,$extra);
	}
}
