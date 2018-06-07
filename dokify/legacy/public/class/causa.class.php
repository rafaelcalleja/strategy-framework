<?php
class causa extends concodigo {
	const NOMBRE_TABLA = 'accidente_causa';
	const NOMBRE_TABLA_COMPLETO = TABLE_CAUSA;
	public function __construct($param, $extra = false) {
		parent::instance($param,$extra);
	}
}
