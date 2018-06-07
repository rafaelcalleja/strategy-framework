<?php
	interface IvalidationStatus {

		const VALIDATED = 0;
		const REJECTED = 1;


		/**	
		  * Instanciar objeto
		  * 
		  */
		public function __construct($param, $extra = false);


		/**	
		  * Devuelve el anexo asociado a esta validación. Puede estar en la tabla anexo_item o en la tabla anexo_item_historico
		  *
		  *
		  * @return anexo
		  *
		  */
		public function getAttachment();


		/**	
		  * Estado de la validación del anexo
		  *
		  *
		  * @return int
		  *
		  */
		public function getStatus();


		/**	
		  * Precio por anexo
		  *
		  *
		  * @return int
		  *
		  */
		public function getAmount();


		/**	
		  * Devuelve el modulo al que pertenece el anexo
		  *
		  *
		  * @return int
		  *
		  */
		public function getRequestableModule();


		/**	
		  * Devuelve el nombre del modulo al que pertenece el anexo
		  *
		  *
		  * @return int
		  *
		  */
		public function getRequestableModuleName();


		/**	
		  * Valida un archivo
		  *
		  * @param $usuario
		  *
		  * @return Bool
		  *
		  */
		public function validar(usuario $usuario);



		/**	
		  * Anular un anexo
		  *
		  * @param $usuario
		  *
		  * @return Bool
		  *
		  */
		public function anular(usuario $usuario);
		

		/**	
		  * Cambia el estado de una validación. Útil se se han equivocado y tienen que cambiar el estado de la validación.
		  *
		  * @param $status <> [ESTADO_VALIDADO, ESTADO_ANULADO]
		  * @param $usuario
		  *
		  * @return Bool
		  *
		  */
		public function changeStatus($status, usuario $usuario);

	}