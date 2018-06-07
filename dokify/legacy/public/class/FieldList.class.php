<?php
	class FieldList extends extendedArray {
		private $keys = array();
		private $colspan = 1;


		public function toComaList(){
			return implode(",", array_keys($this->getArrayCopy()));
		}

		public function getMinColSpan(FormField $campo){
			if ($campo["type"] != "checkbox") { 
				return $this->colspan; 
			}

			if( $previous = $this->getPrevious($campo) ){
				if( $previous["type"] == "checkbox" && !$previous->used ){ return "0"; }
			}
			if( $next = $this->getNext($campo) ){
				if( $next["type"] == "checkbox" ){ return "0"; }
			}

			return $this->colspan;
		}

		public function openLine(FormField &$campo){
			if( $previous = $this->getPrevious($campo) ){
				if( !$previous->offset || $previous->offset == 0 ){ return true; }

				if( !$previous->used && $previous["type"] == "checkbox" && $campo["type"] == "checkbox" ){
					$campo->used = true;
					return false;
				}
			}
			return true;
		}

		public function endLine(FormField $campo){
			if( $offset = $campo->offset ){
				$storageOffset = $this->keys[$offset+1];
				$nextItem = @$this[$storageOffset];

				if( !$campo->used && $nextItem["type"] == "checkbox" && $campo["type"] == "checkbox" ){
					return false;
				}
			}
			return true;
		}

		public function getPrevious($campo){
			if( isset($campo->offset) && $offset = $campo->offset ){
				if( $offset == 0 ){ return true; }

				if (isset($this->keys[$offset-1])) {
					$storageOffset = $this->keys[$offset-1];
					return @$this[$storageOffset];
				}
			}
			
			return null;
		}

		public function getNext($campo){
			$offset = $campo->offset;

			if (is_numeric($offset)) {
				if (isset($this->keys[$offset+1])) {
					$storageOffset = $this->keys[$offset+1];
					return $this[$storageOffset];
				}
			}

			return null;
		}


		public function OffsetSet($offset, $val){
			if( is_object($val) ){
				$val->name = $offset;
				$val->offset = count($this->getArrayCopy());
			}


			if( $offset ){
				$this->keys[] = $offset;
			}
	
			$previous = $this->getPrevious($val);
			if( (isset($previous['type']) && $previous["type"]=="checkbox") && (isset($val['type']) && $val["type"] == "checkbox") ){
				$this->colspan = 4;
			}


			if( isset($val["depends"]) && $depends = $val["depends"] ){
				$affects =  str_replace('[]', '', $offset);

				if( is_string($depends) && $name = trim($depends) ){
					$this[$name]["affects"] = $affects;
				} else {
					$name = array_shift($depends);
					if( isset($this[$name]["affects"]) ){
						$this[$name]["affects"] .= "," . $affects;
					} else {
						$this[$name]["affects"] = $affects;
					}
					$this[$name]["parts"] = implode(",",$depends);
				}
			}
			
			parent::OffsetSet($offset, $val);
		}

	}
?>
