<?php

class EndevePayment extends EndeveModelBase {
	
	function __startup() {
		$this->setName('payment');
	}

	/* public */ function save($sale_id) {
		$id = $this->get($this->_primaryKey);
		if(empty($id)) {
			/* create */
			return parent::save('sales/'.$sale_id.'/payments');
		}else{
			/* update */
			return false;
		}
	}
	
	/* public */ function delete($sale_id, $id=null) {
		return parent::delete('sales/'.$sale_id.'/payments', $id);
	}

	/* public */ function refresh() {
		return false;
	}

	/* public */ function find() {
		return false;
	}
	
}