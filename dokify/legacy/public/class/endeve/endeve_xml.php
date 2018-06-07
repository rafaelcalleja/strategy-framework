<?php

class EndeveXML {

	var $_name = null;
	var $_primaryKey = 'id';
	var $_data = array();
	var $_modified = array();
	var $_errors = array();
	var $_encoding = 'UTF-8';
	var $_lastResponse;
	
	/* protected */ function _reset() {
		$this->_data = array();
		$this->_modified = array();
		$this->_errors = array();
	}
	
	/* public */ function get($name) {
		return $this->__get($name);
	}

	/* public */ function getErrors() {
		return $this->_errors;
	}

	/* public */ function set($name, $value) {
		return $this->__set($name, $value);
	}

	/* public */ function keys() {
		return array_keys($this->_data);
	}

	/* public */ function setName($name) {
		$this->_name = $name;
	}

	/* public */ function getName() {
		return $this->_name;
	}
	
	/* public */ function hasModifications() {
		if(!empty($this->_modified)) {
			return true;
		}
		foreach($this->_data as $key => $objects) {
			if(!is_array($objects)) {
				$objects = array($this->_data[$key]);
			}
			foreach($objects as $object) {
				if(method_exists($object, 'hasModifications')) {
					if($object->hasModifications()) {
						return true;
					}
				}
			}
		}
		return false;
	}

	/* public */ function getEncoding() {
		return $this->_encoding;
	}
	
	/* public */ function asArray() {
		return $this->_data;
	}

	/* public */ function asXML($recursive=true, $onlyModified=false, $includeHeader=true) {
		$xml = '';
		if($includeHeader) {
			$xml.= '<?xml version="1.0" encoding="'.$this->getEncoding().'"?>';
		}
		if(!empty($this->_name)) {
			$xmlName = $this->_vartoxml($this->_name);
			$xml.= "<{$xmlName}>";
		}
		$xml.= $this->_asXML($this->_data, $recursive, $onlyModified);
		if(!empty($this->_name)) {
			$xmlName = $this->_vartoxml($this->_name);
			$xml.= "</{$xmlName}>";
		}
		return $xml;
	}
	
	/* protected */ function _vartoxml($string) {
		return strtr($string, '_', '-');
	}

	/* protected */ function _xmltovar($string) {
		return strtr($string, '-', '_');
	}
	
	/* protected */ function _asXML(&$data, $recursive, $onlyModified) {
		$xml = '';
		foreach($data as $name => $value) {
			$attributes = array();
			if(is_object($value) && $recursive) {
				$needSave = true;
				if($onlyModified && method_exists($value, 'hasModifications')) {
					$needSave = $value->hasModifications();
				}
				if($needSave) {
					if(method_exists($value, 'asXML')) {
						$xml.= $value->asXML($recursive, $onlyModified, /*includeHeader=*/false);
					}else{
						$xml.= "<{$name}";
						$fields = get_object_vars($value);
						$xml.= $this->_asXML($fields, $recursive, $onlyModified);
						$xml.= "</{$name}";
					}
				}
			}else{
				$save = true;
				if($onlyModified) {
					$save = array_key_exists($name, $this->_modified);
					if(!$save && is_array($value)) {
						foreach($value as $object) {
							if(method_exists($object, 'hasModifications')) {
								if($object->hasModifications()) {
									$save = true;
									break;
								}
							}
						}
					}
				}
				if($save) {
					switch(strtolower(gettype($value))) {
						case 'array':
							$attributes['type']= 'array';
							$value = $this->_asXML($value, $recursive, $onlyModified);
							break;
						case 'integer':
							$attributes['type']= 'integer';
							break;
						case 'double':
							$attributes['type']= 'decimal';
							break;
						case 'null':
							$attributes['nil']= 'true';
							break;
					}
					$encodeAttributes = '';
					foreach($attributes as $attributeName => $attribute) {
						$encodeAttributes .= " {$attributeName}=\"{$attribute}\"";
					}
					$xml.= "<{$name}{$encodeAttributes}>";
					$xml.= $value;
					$xml.= "</{$name}>";
					
				}
			}
			
		}
		
		return $xml;
	}

	function __get($name) {
		$name = $this->_vartoxml($name);
		if(isset($this->_data[$name])) {
			return $this->_data[$name];
		}
		if($name == 'errors') {
			return $this->getErrors();
		}
		return null;
	}
	
	function __set($name, $value) {
		$name = $this->_vartoxml($name);
		$this->_data[$name] = $value;
		$this->_modified[$name] = true;
	}
	
	function copy() {
		if(PHP_VERSION > 5) {
			return clone $this;
		}else{
			$copy = $this;
			$copy->fixCopy();
		}
	}

	function fixCopy() {
		unset($this->_data[$this->_primaryKey]);
		if(count($this->data)) {
			$this->_modified = EndeveFunctions::array_combine(array_keys($this->data), array_fill(0, count($this->data), true));
		}
	}
	
	/**  As of PHP 5  */
	function __clone() {
		$this->fixCopy();
	}
	
	/**  As of PHP 5.1.0  */
	function __isset($name) {
		$name = $this->_vartoxml($name);
		return isset($this->_data[$name]);
	}

	/**  As of PHP 5.1.0  */
	function __unset($name) {
		$name = $this->_vartoxml($name);
		unset($this->_data[$name]);
		if(isset($this->_modified[$name])) {
			unset($this->_modified[$name]);
		}
	}
	
	function endeveXML() {
		register_shutdown_function(array(&$this, '__destruct'));
		$args = func_get_args();
		call_user_func_array(array(&$this, '__construct'), $args);
	}
	
	function __destruct() {
	}
	
	function __construct() {
		$this->_reset();
	}
	
	/* public */ function fromXML(&$data, $encoding='UTF-8', $targetEncoding=null) {
		$this->_data = array();
		$this->_modified = array();
		
		if(empty($targetEncoding)) {
			$targetEncoding = $encoding;
		}
		$this->_encoding = $targetEncoding;

		if(is_array($data)) {
			//Recursive object creation
			$xml = &$data;
		}else{
			//First call (string)
			$parser = xml_parser_create('UTF-8');
			xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, $targetEncoding);
			xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
			xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
			if(!xml_parse_into_struct($parser, trim($data), $xml)) {
				return false;
			}
			xml_parser_free($parser);
		}
		
		while(!is_null($element = array_shift($xml))) {
			//prepare the value
			$value = null;
			if(isset($element['value'])) {
				$value = $element['value'];
			}
			if(isset($element['attributes'])) {
				foreach($element['attributes'] as $attributeKey => $attribute) {
					switch($attributeKey) {
						case 'type':
							switch($attribute) {
								case 'integer':
									$value = (integer) $value;
									break;
								case 'decimal':
									$value = (double) $value;
									break;
								case 'array':
									$value = array();
									break;
								case 'datetime':
								default:
									$value = (string) $value;
									break;
							}
							break;
						case 'nil':
							$value = null;
					}
				}
			}
			
			//insert the value
			switch($element['type']) {
				case 'open':
					$newElement = null;
					$elementClass = 'Endeve'.ucfirst($element['tag']);
					$file = 'endeve_' . strtolower($element['tag']) . '.php';
					if( is_readable($file) && class_exists($elementClass)) {
						$newElement = new $elementClass;
						if(!EndeveFunctions::is_a($newElement, get_class($this))) {
							$newElement = null;
						}
					}
					if(!isset($newElement)) {
						$elementClass = get_class($this);
						$newElement = new $elementClass();
					}
					$newElement->setName($element['tag']);
					$newElement->fromXML($xml, $encoding, $targetEncoding);
					if(is_array($value)) {
						//Try save as array
						$vars = $newElement->keys();
						if(count($vars) == 1) {
							//If it has only one tag, else save as object
							$key = reset($vars);
							$newElement = $newElement->get($key);
							if(!is_array($newElement)) {
								$newElement = array($newElement);
							}
						}
					}
					if(isset( $this->_data[$element['tag']] )) {
						//Tag is repeated
						if(!is_array($this->_data[$element['tag']])) {
							$this->_data[$element['tag']] = array($this->_data[$element['tag']]);
						}
						$this->_data[$element['tag']][] = $newElement;
					} else {
						$this->_data[$element['tag']] = $newElement;
					}
					break;
				case 'complete':
					$this->_data[$element['tag']] = $value;
					break;
				case 'close':
					return true;
			}
		}
		return true;
	}
}
