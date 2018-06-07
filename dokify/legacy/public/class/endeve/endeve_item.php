<?php

class EndeveItem extends EndeveModelBase {
	
	function __startup() {
		$this->setName('item');
	}

	/* public */ function save() {
		return false;
	}
	
	/* public */ function delete() {
		return false;
	}

	/* public */ function refresh() {
		return false;
	}

	/* public */ function find() {
		return false;
	}
	
}