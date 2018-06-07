<?php

	class usuarioAPI extends usuario {

		public function get(){
			return array("id" => $this->uid);
		}

		public function post_empresas($uid = NULL){
			if( $uid ){
				$empresa = new empresa($uid);
				if( $empresa->exists() /*&& $this->accesoElemento($empresa)*/ ){
					if( $empresa->update( $_POST, NULL, $this) ){
						return array("result" => 1);
					}
					return array("result" => 0);
				}
			}
		}

		public function get_empresas($uid = NULL){
			if( $uid ){
				$empresa = new empresa($uid);
				if( $empresa->exists() /*&& $this->accesoElemento($empresa)*/ ){
					return array("uid" => $uid, "info" => reset($empresa->getInfo(true)) );
				}
			} else {
				$empresas = $this->getCompany()->obtenerEmpresasInferiores()->toIntList()->getArrayCopy();

				return array(
					"number" => 1, 
					"empresas" => $empresas
				);
			}
		}
	}
