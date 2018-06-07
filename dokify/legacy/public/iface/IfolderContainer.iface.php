<?php

	interface IfolderContainer {

		/**
			OBTENER LAS CARPETAS RAIZ
		**/
		public function obtenerCarpetas($recursive = false, $level = 0, Iusuario $usuario = NULL);
	}
