<?php
class tipoasistencia extends concodigo {
	const NOMBRE_TABLA = 'accidente_tipo_asistencia';
	const NOMBRE_TABLA_COMPLETO = TABLE_TIPO_ASISTENCIA;
	public function __construct($param, $extra = false) {
		parent::instance($param,$extra);
	}
}
