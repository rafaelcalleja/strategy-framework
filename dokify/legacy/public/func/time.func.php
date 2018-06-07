<?php


	function elapsed ($time, $ref = null) {
		$ref = $ref ? $ref : time();
		$tpl = Plantilla::singleton();
		if ($time > $ref) return $tpl("not_happen_yet");

		$diff = $ref - $time;

		if ($diff < 20) return $tpl("just_now");
		if ($diff < 60) return $tpl("less_than_minute");


		$now = new DateTime();
    	$given = new DateTime();
    	$given->setTimestamp($time);

    	// --- interval object
    	$interval = $now->diff($given);


    	foreach ($interval as $prop => $val) {
    		if (strlen($prop) > 1) continue; // skip aux values

    		// --- if we have a value in this unit
    		if ($val) {
    			$str = ($val == 1) ? "{$prop}_ago" : "{$prop}s_ago";

    			return sprintf($tpl($str), $val);
    		}
    	}

		return $diff;
	}


	function get_exec_time(){
		$u=getrusage(); return $u["ru_utime.tv_usec"];
	}

	function get_exec_microtime(){
		return getMicrotime()-($_SESSION["st_microtime"]);
	}


	function getMicrotime() {
		list($milisegundos, $segundos) = explode(" ", microtime());
		return ( (float) $milisegundos + (float) $segundos );
	}