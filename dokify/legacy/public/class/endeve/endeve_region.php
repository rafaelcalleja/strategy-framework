<?php

class EndeveRegion extends EndeveModelBase {
	
	function __startup() {
		$this->setName('region');
	}

	/* public */ function save() {
		return false;
	}
	
	/* public */ function delete($id=null) {
		return false;
	}
	
	/* public */ function refresh() {
		return false;
	}

	/* public */ function find($query=array()) {
		if(is_array($query) && !array_key_exists('country_id', $query)) {
			return false;
		}
		return parent::find('regions', $query);
	}
	
}