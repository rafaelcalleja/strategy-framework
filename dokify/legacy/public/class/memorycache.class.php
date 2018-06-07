<?php
	
	class memorycache {
		private static $instance;
		public $memcache;

		public static function singleton(){
			if( !isset(self::$instance) ){
				$c = __CLASS__;
				self::$instance = new $c();
			}
			return self::$instance;
		}

		public function __construct(){
			$this->memcache = new Memcache;
			$this->memcache->connect('127.0.1.1', 11211);
		}
	}

?>
