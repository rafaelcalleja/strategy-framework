<?php
	interface Iactivable {

		/**

		*/
		public function obtenerElementosActivables(usuario $usuario = NULL);


		/**

		*/
		public function enviarPapelera($parent, usuario $usuario);


		/**

		*/
		public function restaurarPapelera($parent, usuario $usuario);
		

		/**

		*/
		public function inTrash($parent);

		
		/*
 		 * Nos indica si un elemento puede extraerse de la papelera..
		 *
		 */
		public function isActivable($parent = false, usuario $usuario = NULL);


		/*
 		 * @return bool | string - Devolverá true si se puede desactivar este elemento o string informando de por que no.
		 *
		 */
		public function isDeactivable($parent, usuario $usuario);
		

		/**
		  * Elmina permanentemente la relación entre los 2 elementos
		  * 
		  */
		public function removeParent(elemento $elemento, usuario $usuario);

		public function needsConfirmationBeforeTrash($parent, usuario $usuario);
	}
?>
