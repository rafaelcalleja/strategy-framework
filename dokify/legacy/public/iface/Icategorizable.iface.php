<?php
	interface Icategorizable {

		public function obtenerElementosSuperiores();

		public function obtenerAgrupamientos($usuario=false );
	
		public function quitarAgrupadores($arrayIDS, Iusuario $usuario = NULL, $asignados=false );

		public function obtenerAgrupadores($recursividad=null, $usuario=false, $agrupamientos=false, $condicion=false, $forceCurrentClient = false );

		public function asignarAgrupadores($arrayIDS, $usuario=false, $rebote = 0, $replicar=false);

		public function estadoAgrupador(agrupador $agrupador);

		public function obtenerAgrupamientosConRebotes($usuario, $condicion=false, $self=true);

		public function obtenerAgrupamientosAsignados($usuario = false, $includeRelations = false);

		public function lockAll(agrupamiento $agrupamiento, $lock = true );

		public function isAllLocked(agrupamiento $agrupamiento);

		public function obtenerParametrosDeRelacion($filtro=false);

		public function getDuracionValue(agrupador $agrupador);

		public function setDuracionValue(agrupador $agrupador, $duracion, $startDate);


		/* Nos indica las acciones que se pueden realizar en al relacion de un item - agrupador 
		 * Gráficamente podemos ver estas acciones en la opción asignar -> pulsando en el icono (+) que tiene cada agrupador asignado
		 *
		 * @param $agrupamiento
		 * @param $usuario
		 *
		 * return array( array(), array(), array() ) 
		 * 		format: array(innerHTML, className, img, href)
		 */
		public function obtenerAccionesRelacion(agrupamiento $agrupamiento, Iusuario $usuario);
	}
?>
