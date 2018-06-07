<?php

class EndeveSale extends EndeveModelBase {
	
	function __construct($data) {
		if (is_array($data)){
			parent::__construct($data);
		}else if(is_numeric($data)){
			$this->_reset();
			$this->__startup();
			$this->set("id", $data);
		}
	}

	function __startup() {
		$this->setName('sale');
	}

	/*protected*/ function _reset() {
		parent::_reset();
		$this->_data['items'] = array();
		$this->_data['payments'] = array();
	}

	/* static public */ function lastNumber() {
		$response = EndeveBase::exec('sales/new');
		if(isset($this)) {
			$this->_validResponse = $response;
		}
		if(EndeveBase::validResponse()) {
			$sale = $response['xml']->get('sale');
			return $sale->get('last-number');
		}
		return false;
	}

	public function deliver(){
		$id = $this->get($this->_primaryKey);
		return parent::deliver('sales/'.$id.'/deliver');
	}
	
	/* public */ function addItem($item) {
		$this->_data['items'][] = $item;
	}

	/* public */ function addPayment($payment) {
		$this->_data['payments'][] = $payment;
	}

	/* public */ function save() {
		return parent::save('sales');
	}
	
	/* public */ function delete($id=null) {
		return parent::delete('sales', $id);
	}

	/* public */ function refresh() {
		return parent::refresh('sales');
	}

	/* public */ function find($query=array()) {
		return parent::find('sales', $query);
	}
	
}
