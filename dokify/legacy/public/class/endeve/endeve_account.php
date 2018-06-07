<?php

class EndeveAccount extends EndeveModelBase {
	
	function __startup() {
		$this->setName('account');
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

	/* public */ function find() {
		return false;
	}
	
	/* public */ function show() {
		return parent::first('accounts/show');
	}
}