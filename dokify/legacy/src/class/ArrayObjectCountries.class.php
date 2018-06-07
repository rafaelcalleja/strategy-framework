<?php

	class ArrayObjectCountries extends ArrayObjectList {
		

		public function getViewData (elemento $usuario = null, $config = 0, $extraData = null, $options = true) {
			$SQL = "SELECT uid_pais, nombre FROM ". TABLE_PAIS ." 
					WHERE uid_pais IN ({$this->toComaList()}) 
					ORDER BY uid_pais=". pais::SPAIN_CODE." DESC,
							uid_pais=". pais::CHILE_CODE." DESC,
							uid_pais=". pais::FRANCE_CODE." DESC,
							uid_pais=". pais::PORTUGAL_CODE." DESC,
							uid_pais=". pais::ITALY_CODE." DESC,
							uid_pais=". pais::GERMANY_CODE." DESC,
							uid_pais=". pais::UK_CODE." DESC,
							nombre ASC";

			$db = db::singleton();

			$data = $db->query($SQL, true);
			$viewData = array();
			foreach ($data as $value) {
				$viewData[$value["uid_pais"]] = utf8_encode($value["nombre"]);
			}

			return $viewData;
		}

		
	}