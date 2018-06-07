<?php

	class ArrayObjectStates extends ArrayObjectList {
		

		public function getViewData (elemento $usuario = null, $config = 0, $extraData = null, $options = true) 
		{
			$SQL = "SELECT uid_provincia, nombre FROM ". TABLE_PROVINCIA ." WHERE uid_provincia IN ({$this->toComaList()})";

			$db = db::singleton();

			$data = $db->query($SQL, true);
			$viewData = array();
			foreach ($data as $value) {
				$viewData[$value["uid_provincia"]] = utf8_encode($value["nombre"]);
			}

			return $viewData;
		}

		
	}