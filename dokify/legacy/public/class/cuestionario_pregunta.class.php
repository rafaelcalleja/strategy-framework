<?php  

	class cuestionario_pregunta extends base implements Ipregunta, Imodel {

		public static function create(cuestionario $cuestionario, $text){
			$data = array("uid_cuestionario" => $cuestionario->getUID(), "pregunta" => $text);
			return parent::crear($data);
		}

		public function getRespuesta(usuario $usuario, cuestionario_cumplimentacion $estado){
			$filters = array("uid_usuario" => $usuario->getUID(), "uid_cuestionario_cumplimentacion" => $estado->getUID(), "uid_cuestionario_pregunta" => $this->getUID());

			if( $respuestas = cuestionario_respuesta::getAll($filters) ){
				return reset($respuestas);
			}

			return false;
		}

		public static function fields(){
			$fields = array(
				"uid_cuestionario"	=> null,
				"pregunta"			=> null
			);

			return $fields;
		}
	}
?>
