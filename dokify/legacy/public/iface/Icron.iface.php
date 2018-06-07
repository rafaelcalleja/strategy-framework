<?php
	interface Icron {
		public static function cronCall ($time, $force = null);
		public static function cronPeriod ();
	}
?>
