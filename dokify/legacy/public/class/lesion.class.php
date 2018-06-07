<?php
class lesion extends concodigo {
	const NOMBRE_TABLA = 'accidente_lesion';
	const NOMBRE_TABLA_COMPLETO = TABLE_LESION;
	public function __construct($param, $extra = false) {
		parent::instance($param,$extra);
	}
}
