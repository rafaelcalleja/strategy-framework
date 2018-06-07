<?php

/*abstract*/ class EndeveModelBase extends EndeveXML {
	
	/* protected */ function _reset() {
		parent::_reset();
	}
	
	function __construct($data=array()) {
		$this->_reset();
		$this->__startup();
		$this->_data = array_merge($this->_data, $data);
		if(count($data)) {
			$this->_modified = array_merge($this->_modified, EndeveFunctions::array_combine(array_keys($data), array_fill(0, count($data), true)));
		}
	}
	
	/* public */ function getLastResponse() {
		return $this->_lastResponse;
	}
	
	/* public */ function save($model) {
		$this->_errors = array();
		
		$id = $this->get($this->_primaryKey);
		if(empty($id)) {
			$this->_lastResponse = EndeveBase::create($model, $this);
		}else{
			$xml = $this->asXml(/*recursive=*/true, /*onlyModified=*/true);
			$this->_lastResponse = EndeveBase::update($model, $id, $xml);
		}
		
		$response = $this->_lastResponse;
		
		if($this->_validResponse()) {
			$this->_modified = array();
			$data = $response['xml']->asArray();
			if(array_key_exists($this->getName(), $data)) {
				$this->_data = $data[$this->getName()]->asArray();
			}
			return true;
		
		}elseif(EndeveFunctions::is_a($response['xml'], 'EndeveXML')) {
			$data = $response['xml']->asArray();
			if(array_key_exists($this->getName(), $data)) {
				$data = $data[$this->getName()]->asArray();
				if(isset($data['errors'])) {
					$this->_errors = $data['errors'];
				}
			}
		}
		return false;
	}

	/* public */ function delete($model, $id=null) {
		if(isset($this)) {
			$this->_errors = array();
		}

		if(!isset($id)) {
			$id = $this->get($this->_primaryKey);
		}
		if(!empty($id)) {
			$response = EndeveBase::delete($model, $id);
			if(isset($this)) {
				$this->_lastResponse = $response;
			}
			return EndeveModelBase::_validResponse($response);
		}
		return false;
	}


	/* public */ function deliver($model, $id=null) {
		if(isset($this)) {
			$this->_errors = array();
		}
		$this->get($this->_primaryKey);
	
		$response = EndeveBase::deliver($model);
		if(isset($this)) {
			$this->_lastResponse = $response;
		}
		return EndeveModelBase::_validResponse($response);
		return false;
	}
	
	/* public */ function refresh($model) {
		$this->_errors = array();

		$id = $this->get($this->_primaryKey);
		if(empty($id)) {
			return false;
		}
		return EndeveModelBase::find($model, $id);
	}

	/* public */ function find($model, $query=array(), $first=false) {
		if(isset($this)) {
			$this->_errors = array();
		}

		switch(gettype($query)) {
			case 'integer':
				$response = EndeveBase::read($model, $query);
				break;
			default:
				if(!is_array($query)) {
					$query = array('q' => (string)$query);
				}
				$encodeQuery = '';
				foreach($query as $key => $value) {
					$encodeQuery.= urlencode($key).'='.urlencode($value).'&';
				}
				$response = EndeveBase::find($model, $encodeQuery);
				
		}
		
		$return = false;
		
		if(EndeveModelBase::_validResponse($response)) {
			if(is_array($query) && !$first) {
				$xml = $response['xml']->get($model);
				if(is_array($xml)) {
					$return = $xml;
				}else{
					$xml = $response['xml']->get('nil-classes');
					if(is_array($xml)) {
						$return = $xml;
					}
				}
			}else{
				/* return the array */
				$objects = $response['xml']->keys();
				if(count($objects)) {
					$return = $response['xml']->get(reset($objects));
				}
			}
		}
		
		if(isset($this) && $this instanceof self) {
			$this->_reset();
			$this->_lastResponse = $response;
			if(method_exists($return, 'asArray')) {
				$this->_data = $return->asArray();
			}
			if(method_exists($return, 'getEncoding')) {
				$this->_encoding = $return->getEncoding();
			}
		}
		
		return $return;
	}
	
	/* protected */ function first($model, $query=array()) {
		if(isset($this)) {
			return $this->find($model, $query, true);
		}
		return EndeveModelBase::find($model, $query, true);
	}
	
	/* protected */ function _validResponse($response=null) {
		if(!isset($response)) {
			$response = $this->_lastResponse;
		}
		return EndeveBase::validResponse($response);
	}
}
