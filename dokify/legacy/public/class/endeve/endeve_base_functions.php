<?php

class EndeveBaseFunctions {
	
	/*static private*/ function getUrl() {
		return 'https://www.endeve.com/';
	}
	
	/*static public*/ function init($key, $options = array()) {
		EndeveBase::__static('key', $key);
		
		//Options for the cURL transfer
		$valid_options = array(
			CURLOPT_HTTPPROXYTUNNEL, CURLOPT_PROXY, CURLOPT_PROXYUSERPWD, CURLOPT_PROXYAUTH, CURLOPT_CONNECTTIMEOUT, 
			CURLOPT_LOW_SPEED_LIMIT, CURLOPT_LOW_SPEED_TIME, CURLOPT_TIMEOUT, CURLOPT_SSL_VERIFYPEER, CURLOPT_SSL_VERIFYHOST
		);
		if(defined('CURLOPT_CONNECTTIMEOUT_MS')) {
			$valid_option[]= CURLOPT_CONNECTTIMEOUT_MS;
		}
		if(defined('CURLOPT_TIMEOUT_MS')) {
			$valid_option[]= CURLOPT_TIMEOUT_MS;
		}
		if(defined('CURLOPT_PROXYPORT')) {
			$valid_option[]= CURLOPT_PROXYPORT;
		}
		if(defined('CURLOPT_PROXYTYPE')) {
			$valid_option[]= CURLOPT_PROXYTYPE;
		}
		
		$curlOpts = endeveFunctions::array_intersect_key($options, array_flip($valid_options));

		$curlOpts += array(
			CURLOPT_HEADER => false,
			CURLOPT_SSL_VERIFYPEER => TRUE,
			CURLOPT_SSL_VERIFYHOST => 2,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_MAXREDIRS => 0,
		);
		EndeveBase::__static('curlOpts', $curlOpts);
	}

	/*static public*/ function ping() {
		$response = EndeveBase::__exec('ping');
		if(EndeveBase::validResponse()) {
			return $response['total_time'];
		}
		return false;
	}

	/*static public*/ function exec($path) {
		return EndeveBase::__exec($path);
	}

	/*static public*/ function read($model, $id) {
		return EndeveBase::__exec($model, array('id' => $id));
	}

	/*static public*/ function find($model, $query=null) {
		return EndeveBase::__exec($model, array('query' => $query));
	}
	
	/*static public*/ function create($model, &$xml) {
		return EndeveBase::__exec($model, array('xml' => &$xml));
	}

	/*static public*/ function update($model, $id, &$xml) {
		return EndeveBase::__exec($model, array('id' => $id, 'xml' => &$xml));
	}
	
	/*static public*/ function delete($model, $id) {
		return EndeveBase::__exec($model, array('id' => $id, 'delete' => true));
	}

	/*static public*/ function deliver($model, $id = null) {
		return EndeveBase::__exec($model, array('id' => null, 'deliver' => true));
	}
	
	/*static public*/ function getLastResponse() {
		return EndeveBase::__static('lastResponse');
	}
	
	/*static public*/ function validResponse($response=null) {
		if(!isset($response)) {
			$response = EndeveBase::getLastResponse();
		}
		if(isset($response) && !$response['error'] && intval($response['http_code']/100) == 2) {
			return true;
		}
		return false;
	}

	/*static private*/ function __exec($model, $options = array()) {
		if(is_null(EndeveBase::__static('key'))) {
			return false;
		}
		
		$options += array(
			'id' => null,
			'xml' => null,
			'query' => null,
			'delete' => false,
		);
		
		$url = EndeveBase::getUrl().$model;
		$data = '';
		$method = 'GET';
		
		if(EndeveFunctions::is_a($options['xml'], 'EndeveXML')) {
			$data = $options['xml']->asXML();
			$method = 'POST';
		}else{
			$data = &$options['xml'];
		}

		if(!is_null($options['id'])) {
			$url .= '/'.$options['id'];
			if(!empty($data)) {
				$method = 'PUT';
			}
		}
		
		if($options['delete']) {
			$method = 'DELETE';
		}

		if(@$options['deliver']) {
			$method = 'POST';
		}
		
		//dump("METHOD {$method} - " . $url.'.xml?'.$options['query']);
		$ch = curl_init();
		
		if( !EndeveFunctions::curl_setopt_array($ch, EndeveBase::__static('curlOpts') +
			array(
				CURLOPT_URL => $url.'.xml?'.$options['query'],
				CURLOPT_USERPWD => rawurlencode(EndeveBase::__static('key')).':'.rawurlencode('bazinga'),
				CURLOPT_HTTPHEADER => array(
					'Content-Type: application/xml',
					'Expect:', //Some servers (like Lighttpd) will not process the curl request without this header and will return error code 417 instead. 
				),
				CURLOPT_CUSTOMREQUEST => $method,
				CURLOPT_POSTFIELDS => $data,
			))
		) {
			return false;
		}
		
		$response = array();
		$response['data'] = trim(curl_exec($ch));
		$response['error'] = curl_errno($ch);
		$response['format_error'] = curl_error($ch);
		$response += curl_getinfo($ch);
		curl_close($ch);
		$response['xml'] = null;
		if($method != 'DELETE') {
			if(EndeveBase::validResponse($response)) {
				if(class_exists('EndeveXml')) {
					$response['xml'] = new EndeveXml();
					if(!$response['xml']->fromXML($response['data'])) {
						$response['xml'] = null;
					}
				}
			}elseif(!empty($response['data']) && class_exists('EndeveError')) {
				switch($method) {
					case 'POST':
					case 'PUT':
						$response['xml'] = new EndeveXml();
						if(!$response['xml']->fromXML($response['data'])) {
							$response['xml'] = null;
						}
				}
			}
		}
		
		return EndeveBase::__static('lastResponse', $response);
	}
	
	/* PHP4 static properties fix */
	/*static private*/ function __static($key, $value=null) {
		static $options = array();
		if(isset($value)) {
			$options[$key] = $value;
		}
		return isset($options[$key])? $options[$key] : null;
	}
}
