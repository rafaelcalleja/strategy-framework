<?php
	class ArrayIntList extends extendedArray {

		public static function factory($data) {
			$isNum = is_numeric(str_replace(",", "", $data));

			if ($isNum) {
				$array = explode(",", $data);
				return new self($array);
			}
			
			return false;
		}

		public function toObjectList($type, $class = 'ArrayObjectList'){
			$coleccion = new $class;

			foreach($this as $i => $uid){
				$coleccion[] = new $type($uid);
			}
			return $coleccion;
		}


		public function sort ($reverse = false) {
			$arr = $this->getArrayCopy();

			if ($reverse) {
				arsort($arr);
			} else {
				asort($arr);
			}

			return new self ($arr);
		}

		public function toComaList(){
			return $this->__toString();
		}

	}
?>
