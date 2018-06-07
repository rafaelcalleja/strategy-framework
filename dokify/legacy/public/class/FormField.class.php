<?php
	class FormField extends ArrayObject {		
		public $name;

		public function getInnerHTML(){
			$tpl = Plantilla::singleton();
			$display = $this->name;

			if( strstr($display,"[]") ){
				$display  = str_replace("[]","",$display);
			}

			if( isset($this["innerHTML"]) ){
				$innerHTML = $tpl->getString($this["innerHTML"]);
			} else {
				$innerHTML = $tpl->getString($display);
			}

			return $innerHTML;
		}

		public function isHidden(FieldList $coleccion){
			$array = $this->getArrayCopy();
			if( $depends = $array["depends"] ){

				// Solo hay una dependencia simple Y verificamos que esta existe
				if( is_string($depends) ){
					if( isset($coleccion[$depends]) && $depends = $coleccion[$depends] ){

						// Si la propia dependencia, tiene a su vez dependencias y esta oculta...
						if( isset($depends["depends"]) && $depends->isHidden($coleccion) ){
							return true;
						}

						// Si la dependencia es un INPUT se basa en OF / OFF
						if( $depends["tag"] == "input" && $depends["type"] == "checkbox" && !$depends["value"] ){
							return true;
						}

						return false;
					} else {
						trigger_error("Dependencia no encontrada...");
					}
				} 

				if( is_traversable($depends) ){
					$name = array_shift($depends);
					$parts = $depends;

					if( isset($coleccion[$name]) && $depends = $coleccion[$name] ){
						if( !$depends["value"] || !in_array($depends["value"], $parts) ){
							return true;
						}
					}
				}

			}
			return false;
		}
	}
?>
