<?php

	class cuestionario extends base implements Icuestionario, Imodel {
		
		public function getPreguntas(){
			return cuestionario_pregunta::getAll( array("uid_cuestionario" => $this->getUID() ) );
		}
		
		public function getEstado(usuario $usuario){
			if( $estados = cuestionario_cumplimentacion::getAll( array("uid_cuestionario" => $this->getUID(), "uid_usuario" => $usuario->getUID()) ) ){
				return reset($estado);
			}
			return false;
		}
		
		public function activar(){
			return $this->modificar( array("disponible" => 1) );
		}
		
		public function desactivar(){
			return $this->modificar( array("disponible" => 0) );
		}
		
		public static function fields(){
			$fields = array(
				"uid_usuario"			=> null, 
				"nombre" 	=> null,  // el nombre parece redundante, ya que queda algo asi: cuestionario.nombre_cuestionario (2 veces cuestionario)
				"disponible" 			=> true
			);

			return $fields;
		}
		
	}
?>
