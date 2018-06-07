<?php
	
	class cache {
		const TIMEOUT = 60;

		private static $instance;
		private $storage;

		private function __construct(){
			$this->storage = self::getStorage();
		}

		private static function getStorage() {
			// if (class_exists('Redis')) {
			// 	$storage = new RedisStorage();
			// 	if (!$storage->ping()) unset($storage);
			// }

			// Default storage
			if (!isset($storage)) $storage = new ArrayCacheStorage();
			return $storage;
		}

		public static function singleton(){
			if( !isset(self::$instance) ){
				$c = __CLASS__;
				self::$instance = new $c();
			}
			return self::$instance;
		}

		public static function exec() {
			$cache = self::singleton();
			$args = func_get_args();
			$fn = array_shift($args);

			return call_user_func_array(array($cache, $fn), $args);
		}

		public function clear($key=false){
			return $this->storage->clear($key);
		}

		public function set($key, $value, $timeout = self::TIMEOUT) {
			return $this->addData($key, $value, $timeout);
		}

		public function addData($name, $data, $timeout = self::TIMEOUT ){
			if (defined("NO_CACHE_OBJECTS")) return true;
			if (is_bool($data)) $data = $data ? 'true' : 'false';

			// save permanently until manual flush
			if ($timeout===true) return $this->storage->save($name, $data);

			// save with ttl
			return $this->storage->set($name, $data, $timeout);
		}

		public function deleteData($name){
			return $this->delete($name);
		}

		public function delete($name){
			return $this->storage->delete($name);
		}

		public function get($key) {
			return $this->getData($key);
		}

		public function getData($name){
			$value = $this->storage->get($name);
			if ($value=='true') return true;
			if ($value=='false') return false;
			if ($value) return $value; 
			return null;
		}
	}
?>
