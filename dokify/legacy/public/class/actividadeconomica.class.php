<?php
class actividadeconomica extends concodigo {
	const NOMBRE_TABLA = 'accidente_actividad_economica';
	const NOMBRE_TABLA_COMPLETO = TABLE_ACTIVIDAD_ECONOMICA;
	public function __construct($param, $extra = false) {
		parent::instance($param,$extra);
	}
}
