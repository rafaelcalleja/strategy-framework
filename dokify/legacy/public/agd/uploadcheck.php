<?php
	//error_reporting(E_ALL);
	//ini_set('display_errors', '1');

	if ( isset($_REQUEST['pk']) ) {

		include("../api.php");

		if( is_callable(uploadprogress_get_info) ){
			$info = uploadprogress_get_info($_REQUEST['pk']);
		} else {
			echo time();
			exit;
		}

		if( !count($info) ){
			$data = array("data"=>"null");
			print json_encode($data);
			exit;
		}

		$maxSize = $usuario->maxUploadSize();
		if( $info["bytes_total"] > $maxSize ){
			$MBmaxSize = round((($maxSize/1024)/1024),2);
			$MDcurrentSize = round((($info["bytes_total"]/1024)/1024),2);
			$data = array("data" => -1, "max" => $MBmaxSize, "maxbytes" => $maxSize, "current" => $MDcurrentSize, "currentbytes" => $info["bytes_total"]);
			print json_encode($data);
			exit;
		}

		if( $info["bytes_uploaded"] == 0 ){
			$data = array("data"=>0);
			print json_encode($data);
		} else {
			$data = array("data" => round(100 * $info["bytes_uploaded"] / $info["bytes_total"] ) );
			print json_encode($data);
		}

		exit ;
	}
?>
