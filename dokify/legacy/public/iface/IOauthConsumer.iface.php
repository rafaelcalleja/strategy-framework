<?php

	interface IOauthConsumer {

		/* return an instance of a IConsumer or return null on not found */
		public static function findByKey($key);

		/* Create in the DB a consumer with a given key & secret */
		// public static function create($key,$secret);

		/* Returns if the consumer is active */
		public function isActive();

		/* Returns the consumer key */
		public function getKey();

		/* Returns the consumer secret key */
		public function getSecretKey();

		/* check if nonce exist for a specified consumer */
		public function hasNonce($nonce,$timestamp);

		/* Add a nonce to the nonce cache */
		public function addNonce($nonce);
	}

?>
