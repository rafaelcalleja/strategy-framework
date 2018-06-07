<?php

	class publicfile extends elemento {

		/**
			CONSTRUIR EL OBJETO, LLAMA AL METODO INSTANCE DE LA CLASE ELEMENTO
		*/
		public function __construct($param, $extra = false){
			$this->tipo = __CLASS__;
			$this->tabla = TABLE_PUBLICFILE;
			$this->nombre_tabla = "publicfile";
			parent::instance($param, $extra);
		}

		public function getToken() {			
			return $this->obtenerDato('token');
		}

		public function getUrl() {			
			return $this->obtenerDato('url');
		}

		public static function getByToken($token) {	
			$db = db::singleton();
			$sql = "SELECT uid_publicfile FROM ".TABLE_PUBLICFILE." WHERE token = '".$token."'";		
			if( $uid = $db->query($sql, 0, 0) ){
 				return new publicfile($uid);
 			} else return false;
		}

		public static function deleteExpired(){
			$db = db::singleton();

			$SQL = "DELETE FROM ". TABLE_PUBLICFILE ."
				WHERE (datediff( now(), expired_time ) > 0)
			";

			if( $db->query($SQL) ){
				return $db->getAffectedRows();
			}

			return false;
		}

		static public function cronCall($time, $force = false, $items = NULL){
			$m = date("i", $time);
			$h = date("H", $time);
			$w = date("w", $time);

			if( ($h == 02 && $m == 15) || $force ){  
				$deletes = self::deleteExpired(); 
			}
			
			return true;
		}

		public static function defaultData($data, Iusuario $usuario = null) {
			if (!isset($data['path'])) {
				throw new Exception("Imposible crear objeto publicfile sin campo path");
			} else {
				$archivo = new archivo ($data['path']);
				$downloadName = isset($data["downloadName"]) ? $data["downloadName"] : $archivo->getFileName();
				$timeUrl = ( isset($data["timeUrl"]) && strtotime($data["timeUrl"]) ) ? $data["timeUrl"] : '3 hours';
				$data["url"] = archivo::getTemporaryPublicURL($data['path'], $downloadName, $timeUrl);
				if ($data["url"] === false) {
					throw new Exception("error_url_s3_publicfile");
				}
				$data["token"] = MD5(usuario::randomPassword());
				$data["expired_time"] = date("Y-m-d H:i:s", strtotime($timeUrl) );
			}
			return $data;
		}

		public static function publicFields($modo, elemento $objeto = null, Iusuario $usuario = null, $tab = false){
			$arrayCampos = new FieldList;
			$arrayCampos["token"] = new FormField(array());
			$arrayCampos["url"] = new FormField(array());
			$arrayCampos["expired_time"] = new FormField(array());
			return $arrayCampos;
		}

	}
?>