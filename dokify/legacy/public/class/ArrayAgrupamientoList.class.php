<?php

	class ArrayAgrupamientoList extends ArrayObjectList {
		

		public function toAgrupadorList ($conditions = array(), $return = false) {
			$SQL = "SELECT uid_agrupador FROM ". TABLE_AGRUPADOR ." WHERE 1";

			if (count($this)) {
				$SQL .= " AND uid_agrupamiento IN ({$this->toComaList()})";

				if ($condition = agrupador::conditionSQL($conditions)) {
					$SQL .= " AND $condition";
				}
			} else {
				$SQL .= " AND 0";
			}

			if ($return) {
				return $SQL;
			}

			if ($list = db::get($SQL, "*", 0, 'agrupador')) {
				return new ArrayAgrupadorList($list);
			}

			return new ArrayAgrupadorList;
		}

		
	}