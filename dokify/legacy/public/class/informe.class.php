<?php
	class informe extends basic {
		public $elementoFiltro;

		const ESTADO_CARGADO = 1;
		const ESTADO_VACIO = 0;

		public function __construct( $param , $elemento ){
			$this->tipo = "informes";
			$this->tabla = TABLE_INFORME;
			$this->elementoFiltro = $elemento;
			$this->instance( $param, false );
		}
		
		public function descargar(){
			archivo::descargar($this->getPath(), $this->getUserVisibleName());
		}

		public static function anexar($file, $elemento){
			$db = db::singleton();

			$relativePath =  "informes/". $elemento->getType() . "_". $elemento->getUID() . "/";
			$dir =  DIR_FILES . $relativePath;

			if( !is_dir($dir) ){
				mkdir( $dir, 0777, true );
			}


			$realFileName = archivo::getFileName($file['name']);
			$fileName = "inf_" . time() . "." . archivo::getExtension($file['tmp_name']);
			$relativeFile = $relativePath . $fileName;
			$absoluteFile = $dir .  $fileName;

			if( filesize($file['tmp_name']) ){
				if( is_readable($file['tmp_name']) ){
					if ( !copy($file['tmp_name'], $absoluteFile) ) {
						return "error_copiar_archivo";
					}
				} else {
					return "error_leer_archivo";
				}
			}
			
			
			$sql = "INSERT INTO ". TABLE_INFORME ." ( alias, path, uid_elemento, uid_modulo ) VALUES (
				'$realFileName', '$relativeFile', ".$elemento->getUID().", ". $elemento->getModuleId()."
			)";
			
			if( $db->query($sql) ){
				return new informe( $db->getLastId(), $elemento);
			} else {
				return false;
			}
		}

		public function getUserVisibleName(){
			$info = $this->getInfo();
			return $info["alias"];
		}

		public function getPath(){
			$info = $this->getInfo();
			return DIR_FILES . $info["path"];
		}

		public function getLoadStatus($toString=false){
			$path = $this->getPath();

			if( is_file($path) && is_readable($path) ){
				$estado = informe::ESTADO_CARGADO;
			} else {
				$estado = informe::ESTADO_VACIO;
			}

			if( $toString ){
				$tpl = Plantilla::singleton();
				return $tpl->getString("informe_" . $estado );
			}
	
			return $estado;
		}
	}
?>
