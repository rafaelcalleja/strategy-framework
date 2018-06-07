<?php
	if ( isset($_REQUEST['pk']) ) {

		$dataArray["upload"] = array("key"=> $_REQUEST['pk']);

		if( is_callable('uploadprogress_get_info') ){
			$info = uploadprogress_get_info( $_REQUEST['pk'] );
		
			if( !count($info) ){
				$dataArray["upload"]["data"] = "null";
			} else {

				// ---- Totales
				$MDcurrentSize = round((($info["bytes_total"]/1024)/1024),2);
				$dataArray["upload"]["total"] = $MDcurrentSize;
				$dataArray["upload"]["currentbytes"] = $info["bytes_total"];

				// ---- Cargados...
				$MDuploadSize = round((($info["bytes_uploaded"]/1024)/1024),2);
				$dataArray["upload"]["upload"] = $MDuploadSize;
				$dataArray["upload"]["uploadbytes"] = $info["bytes_uploaded"];

				// ---- Totales usuario
				$maxSize = $usuario->maxUploadSize();
				$MBmaxSize = round((($maxSize/1024)/1024),2);
				$dataArray["upload"]["max"] = $MBmaxSize;
				$dataArray["upload"]["maxbytes"] = $maxSize;

				if( $info["bytes_total"] > $maxSize ){
					$dataArray["upload"]["data"] = -1;
				} else {
					if( $info["bytes_uploaded"] == 0 ){
						$dataArray["upload"]["data"] = 0;
					} else {
						$dataArray["upload"]["data"] = round(100 * $info["bytes_uploaded"] / $info["bytes_total"] );
					}
				}

			}
		} else {
			$dataArray["upload"] = time();
		}
	}
?>
