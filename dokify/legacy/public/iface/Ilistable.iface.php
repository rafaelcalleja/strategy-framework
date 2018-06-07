<?php
	interface Ilistable {

		const DATA_CONTEXT = 'context';
		const DATA_CONTEXT_LIST_EMPLEADO = 'list_empleado';
		const DATA_CONTEXT_LIST_MAQUINA = 'list_maquina';
		const DATA_CONTEXT_DESCARGABLES = 'descargables';
		const DATA_CONTEXT_LISTADO = 'listado';
		const DATA_CONTEXT_TREE = 'tree';
		const DATA_CONTEXT_LIST_PRICES = 'prices_validation';
		const DATA_CONTEXT_FIRM = 'firm';
		const DATA_CONTEXT_ATTACH = 'attach';
		const DATA_CONTEXT_INFO = 'info';
		const DATA_CONTEXT_HOME = 'home';
		
		const DATA_MODULO = 'modulo';

		const DATA_ELEMENT = 'elemento';

		const DATA_PARENT = 'parent';
		
		const DATA_SEARCH = 'search';
		const DATA_COMEFROM = 'comefrom';
		const DATA_REFERENCE = 'reference';
		

		/**	
		  * Crea una estructura predefinida para mostrar información dinámica representada como columnas de una fila en una tabla de datos
		  * 
		  */
		public function getInlineArray(Iusuario $usuario = NULL, $config = false, $data = NULL);


		/**	
		  * Crea una estructura predefinida para mostrar información dinámica representada como columnas de una fila en una tabla de datos
		  * 
		  *	@return string | URL que se abrirá cuando hagamos click sobre la linea
		  */
		public function getClickURL(Iusuario $usuario = NULL, $config = false, $data = NULL);

	}
