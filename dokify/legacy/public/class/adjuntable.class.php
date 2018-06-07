<?php

	class adjuntable extends elemento {
	
		public function obtenerAdjuntos(){
			$sql = "SELECT uid_adjunto FROM ". TABLE_ADJUNTO . " WHERE uid_modulo = {$this->getModuleId()} AND uid_elemento = {$this->getUID()}";
			$coleccion = $this->db->query($sql, "*", 0, "adjunto");
			if( count($coleccion) ){
				return new ArrayObjectList($coleccion);
			}
			return false;
		}

		public function attach($file, $data){

			$splittedName = explode(".", $file["tmp_name"]);
			$rutaCarpeta = DIR_ADJUNTOS;
			$fileDBName = time() . "." . end($splittedName);
			$rutaArchivo = $rutaCarpeta . $fileDBName;
			$sqlFileName = "adjuntos/" . $fileDBName;
			$s3 = archivo::getS3();

			if( !$s3 && !is_dir($rutaCarpeta) )	mkdir( $rutaCarpeta, 0777, true );

			// Para optimizar el tiempo de espera, si tienemos S3 activado copiamos directamente los ficheros
			// Si comentamos este IF el entorno local y el de producción deberían seguir funcionando, de forma mas lenta
			if( $s3 ){
				// Copy tmp file a remove it
				if( !archivo::tmp($file["tmp_name"], NULL, $sqlFileName) ) throw new Exception("error_copiar_archivo");

			} else {
				// Recover temporary file
				if( !$filedata = archivo::tmp($file["tmp_name"]) ) throw new Exception("error_copiar_archivo");
				// Write to final destination
				if( !archivo::escribir($rutaArchivo, $filedata) ) throw new Exception("error_copiar_archivo");
			}

			if( !archivo::is_readable($rutaArchivo) ) throw new Exception("error_leer_archivo");

			$name = ( isset($data["nombre"]) && trim($data["nombre"]) ) ? utf8_decode(db::scape($data["nombre"])) : $fileDBName;

			$sql = "INSERT INTO ". TABLE_ADJUNTO ." ( uid_elemento, uid_modulo, file, name ) VALUES (
				{$this->getUID()}, {$this->getModuleId()}, '$fileDBName', '$name'
			)";

			if( $s3 ){ archivo::tmp($file["tmp_name"], NULL, TRUE); }

			if( $this->db->query($sql) ){
				return true;
			}

			return false;
		}

	}

?>
