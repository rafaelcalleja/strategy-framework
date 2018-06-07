<?php
class tipotrabajo extends concodigo {
	const NOMBRE_TABLA = 'accidente_tipo_trabajo';
	const NOMBRE_TABLA_COMPLETO = TABLE_TIPO_TRABAJO;
	public function __construct($param, $extra = false) {
		parent::instance($param,$extra);
	}
}
