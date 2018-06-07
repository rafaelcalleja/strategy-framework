<?php
class desviacion extends concodigo {
	const NOMBRE_TABLA = 'accidente_desviacion';
	const NOMBRE_TABLA_COMPLETO = TABLE_DESVIACION;
	public function __construct($param, $extra = false) {
		parent::instance($param,$extra);
	}
}
