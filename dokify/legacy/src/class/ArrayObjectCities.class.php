<?php

	class ArrayObjectCities extends ArrayObjectList {
		

		public function getViewData (elemento $usuario = null, $config = 0, $extraData = null, $options = true){
			$SQL = "SELECT uid_municipio, nombre FROM ". TABLE_MUNICIPIO ." WHERE uid_municipio IN ({$this->toComaList()}) ORDER BY nombre";

			$db = db::singleton();

			$data = $db->query($SQL, true);
			$viewData = array();
			foreach ($data as $value) {
				$viewData[$value["uid_municipio"]] = utf8_encode($value["nombre"]);
			}

			return $viewData;
		}

		
	}