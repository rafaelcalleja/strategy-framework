<?php 
	
	class cuestionario_cumplimentacion extends base implements Icumplimentacion, Imodel{

		public function getCuestionario(){
			if( $uid = $this->obtenerDato("uid_cuestionario") ){
				return new cuestionario($uid);
			}

			// Si las claves ajenas estan bien no deberia ocurrir nunca ...
			throw new Exception("No existe el cuestionario asociado");
		}

		public function getValidacion(){
			return $this->obtenerDato("estado_validacion");
		}

		public function validar($estado){
			return $this->modificar( array("estado_validacion" => $estado) );
		}

		public static function fields(){
			$fields = array(
				"uid_cuestionario"	=> null, 
				"uid_usuario"		=> null,
				"estado_validacion"	=> Icumplimentacion::VALIDACION_DEFAULT
			);
			return $fields;
		}
	}
?>
