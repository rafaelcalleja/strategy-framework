<?php

class EndeveLanguage extends EndeveModelBase {
	
	function __startup() {
		$this->setName('language');
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
		return parent::find('languages', $query);
	}
	
}