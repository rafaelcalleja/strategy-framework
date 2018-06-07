<?php

class ArrayAssignmentList extends ArrayObjectList
{

	public function getGroups () {
		if (count($this) == 0) {
			return new ArrayAgrupadorList;
		}

		// try cached groups first
		$cached = $this->each('getGroup');
		if (count($cached) === count($this)) {
			return $cached;
		}

		$table 	= TABLE_AGRUPADOR . '_elemento';
		$list 	= $this->toComaList();
		$SQL 	= "SELECT uid_agrupador FROM {$table} WHERE uid_agrupador_elemento IN ({$list})";

		if ($array = db::get($SQL, '*', 0, 'agrupador')) {
			return new ArrayAgrupadorList($array);
		}

		return new ArrayAgrupadorList;
	}
}
