<?php
	class RedisStorage {
		const REDIS_SERVER_KEY 	= 'redis.server';
		const REDIS_PORT = 6379;

		const DEFAULT_TIMEOUT = 60;
		protected $redis;
		protected $data;

		public function __construct() {
			$this->redis = new Redis();
			$this->redis->connect(self::getServer(), self::REDIS_PORT, 2);
			$this->data = new ArrayCacheStorage();
		}

		public function ping() {
			try {
				return $this->redis->ping();
			} catch (RedisException $e) {
				// no redis server 
			}
		}

		public function get($key){
			if ($value = $this->data->get($key)) {
				return $value;
			} else {
				if ($value = $this->redis->get($key)) {
					$this->data->set($key, $value);
					return $value;
				}
			}
		}

		public function delete($key){
			return $this->redis->delete($key);
		}

		public function clear($key=false){
			if ($key) {
				$keys = $this->redis->keys($key);
				return $this->redis->delete($keys);
			} else {
				return $this->redis->flushAll();
			}
		}

		public function save($key, $value){
			$this->data->save($key, $value);
			return $this->redis->set($key, $value);
		}

		public function set($key, $value, $timeout = RedisStorage::DEFAULT_TIMEOUT){
			
			$this->data->set($key, $value);
			//return $this->__asyncRedisWrite($key, $value, $timeout);
			return $this->redis->setex($key, $timeout, $value);
		}

		public static function getServer() {
			return ($server = @trim(get_cfg_var(self::REDIS_SERVER_KEY))) ? $server : "127.0.0.1:6379";
		}

		private function __asyncRedisWrite($key, $value, $timeout = false) {
			$cmd = DIR_ROOT . '/func/cmd/redismanager.php';
			archivo::php5exec($cmd, array($key, base64_encode($value), $timeout));
			return true;
		}
	}