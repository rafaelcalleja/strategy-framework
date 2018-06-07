<?php
function getfilecontent($file, $start=false, $end=false){
	$data = file_get_contents($file);
	if( $start ){
		$aux = explode("\n", $data);
		if( $end ) $end = $end - $start;
		$data = implode("\n", array_slice( $aux, $start, $end, true ));
	}
	return $data;
}
?>