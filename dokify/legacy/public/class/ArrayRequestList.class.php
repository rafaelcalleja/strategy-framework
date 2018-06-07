<?php

	class ArrayRequestList extends ArrayObjectList {

		/*** 
		   *	Get all attachments from a requests collection
		   * 
		   *
		   *
		   */
		public function getCompletionTypes () {
			if (count($this) === 0) return [];

			$db 		= db::singleton();
			$table 		= TABLE_DOCUMENTOS_ELEMENTOS . " INNER JOIN ". TABLE_DOCUMENTO_ATRIBUTO . " USING (uid_documento_atributo)";
			$set 		= $this->toComaList();
			$SQL 		= "SELECT req_type FROM {$table} WHERE uid_documento_elemento IN ({$set}) GROUP BY req_type";
			$types		= $db->query($SQL, '*', 0);


			return array_map('intval', $types);
		}

		/*** 
		   *	Get all attachments from a requests collection
		   * 
		   *
		   *
		   */
		public function getRequirements () {
			if (count($this) === 0) return new ArrayObjectList;

			$db 		= db::singleton();
			$table 		= TABLE_DOCUMENTOS_ELEMENTOS;
			$set 		= $this->toComaList();
			$SQL 		= "SELECT uid_documento_atributo FROM {$table} WHERE uid_documento_elemento IN ({$set}) GROUP BY uid_documento_atributo";
			$collection	= $db->query($SQL, '*', 0, "documento_atributo");


			return new ArrayObjectList($collection);
		}

		/*** 
		   *	Get all attachments from a requests collection
		   * 
		   *
		   *
		   */
		public function getOwnerCompanies () {
			if (count($this) === 0) return new ArrayObjectList;

			$db 		= db::singleton();
			$table 		= TABLE_DOCUMENTOS_ELEMENTOS . " INNER JOIN ". TABLE_DOCUMENTO_ATRIBUTO . " USING (uid_documento_atributo)";
			$set 		= $this->toComaList();
			$SQL 		= "SELECT uid_empresa_propietaria FROM {$table} WHERE uid_documento_elemento IN ({$set}) GROUP BY uid_empresa_propietaria";
			$collection	= $db->query($SQL, '*', 0, "empresa");


			return new ArrayObjectList($collection);
		}


		/*** 
		   *	Get all attachments from a requests collection
		   * 
		   *
		   *
		   */
		public function getAttachments ($class) {
			if (count($this) === 0) return new ArrayAnexoList;

			$db 		= db::singleton();
			$view 		= TABLE_DOCUMENTO . "_{$class}_estado";
			$set 		= $this->toComaList();
			$SQL 		= "SELECT uid_anexo_{$class} FROM {$view} WHERE uid_solicituddocumento IN ({$set})";
			$collection	= $db->query($SQL, '*', 0, "anexo_{$class}");


			return new ArrayAnexoList($collection);
		}

		/*** 
		   *	Get all status from a requests collection
		   * 
		   *
		   *
		   */
		public function getStatuses ($class) {
			if (count($this) === 0) return [];

			$db 		= db::singleton();
			$view 		= TABLE_DOCUMENTO . "_{$class}_estado";
			$set 		= $this->toComaList();
			$status 	= "IF (estado IS NULL, ". documento::ESTADO_PENDIENTE .", estado)";
			$SQL 		= "SELECT {$status} FROM {$view} WHERE uid_solicituddocumento IN ({$set}) GROUP BY estado";
			$statuses 	= $db->query($SQL, '*', 0);


			return new ArrayIntList(array_map('intval', $statuses));
		}



		/*** 
		   *	Método pensado para tratar notificaciones de usuario (con la nueva nomenclatura esto deberíamos moverlo)
		   * 
		   *
		   * 	COVIERTE ESTA COLECCION EN UN ARRAYOBJECTLIST QUE CADA ITEM ES UN ArrayRequestGroupedList. 
		   *	Obteniendo las solicitudes agrupadas por elemento, modulo y empresa solicitante para poder tratar las notificaciones
		   *
		   */
		public function toArrayGroupedList(){
			$arrayByElement = new ArrayObjectList();
			foreach ($this as $request) {
				$flag = false;
				foreach ($arrayByElement as $requestByElement) {
					if ($requestByElement->addCompanyRequest($request)) {
						$flag = true;
						break;
					} 
				}
				if (!$flag) {
					$new = new ArrayRequestGroupedList();
					$new->addCompanyRequest($request);
					$arrayByElement[] = $new;
				}
			}
			return $arrayByElement;
		}
		
	}