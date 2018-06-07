<?php
	interface IfileId {

		/**	
		  * Devuelve un hash único que diferencia al archivo anexado. Este tiene que ser unico entre las tablas, anexo_item y
		  * anexo_historico_item (item ['empresa', 'empleado', 'maquina']). Función recursiva hasta que genere hash único.
		  *
		  * @return hash
		  *
		  */
		public static function generateFileId($filePath = null);


		/**	
		  * Obtener el documento al que hace referencia el fileID
		  *
		  *
		  * @return documento
		  *
		  */
		public function getDocument();


		/**	
		  * Obtener el fichero al que esta asociado el fileID
		  *
		  *
		  * @return string | path del documento
		  *
		  */
		public function getFile();


		/**	
		  * Obtiene el id del tipo de item al que se el solicita el documento (empresa, empleado o maquina)
		  *
		  *
		  * @return int | idModule
		  *
		  */
		public function getModule();


		/**	
		  * Devuelve conjunto anexos asociados a un mismo id_file
		  *
		  * @param empresa partner
		  * @param isUrgent
		  * @param language
		  *
		  * @return arrayObjectList<anexo>
		  *
		  */
		public function getAttachments(empresa $partner);

	}