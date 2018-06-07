<?php
	  
	class cuestionario_respuesta extends base implements Irespuesta, Imodel {
		
		public function validar($valor){
			return $this->modificar( array("validacion" => $valor) );
		}

		public function getText(){
			return $this->obtenerDato("respuesta");
		}

		public static function create(cuestionario_cumplimentacion $cuestionarioCumplimentacion, cuestionario_pregunta $pregunta, usuario $usuario, $text){
			$data = array(
				"uid_cuestionario"			=> $cuestionarioCumplimentacion->getCuestionario()->getUID(), 
				"uid_cuestionario_cumplimentacion"	=> $cuestionarioCumplimentacion->getUID(),
				"uid_cuestionario_pregunta" => $pregunta->getUID(),
				"uid_usuario"				=> $usuario->getUID(),
				"respuesta"					=> $text
			);
			return parent::crear($data);
		}


		public static function fields(){
			$fields = array(
				"uid_cuestionario_cumplimentacion"	=> null,
				"uid_cuestionario" 			=> null, 
				"uid_cuestionario_pregunta" => null, 
				"uid_usuario"				=> null, 
				"respuesta"					=> null,
				"validacion"				=> 0
			);
			return $fields;
		}
	}
?>
