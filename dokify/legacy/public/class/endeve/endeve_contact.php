<?php

class EndeveContact extends EndeveModelBase {
	
	function __startup() {
		$this->setName('contact');
	}

	/* public */ function save() {
		return parent::save('contacts');
	}
	
	/* public */ function delete($id=null) {
		return parent::delete('contacts', $id);
	}
	
	/* public */ function refresh() {
		return parent::refresh('contacts');
	}

	/* public */ function find($query=array()) {
		return parent::find('contacts', $query);
	}
	
}