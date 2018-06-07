<?php
	require("../api.php");

	if( !isset($_REQUEST["t"]) || !isset($_REQUEST["f"]) || !isset($_REQUEST["v"]) ){
		die("NO SE ESPECIFICARON TODOS LOS PARAMETROS");
	}

	$tablename = strtolower(db::scape($_REQUEST["t"]));
	$table = constant('TABLE_' . strtoupper($tablename));
	$field = db::scape($_REQUEST["f"]);
	$value = utf8_decode(db::scape($_REQUEST["v"]));

	//compare type
	//$compare = $_REQUEST["ct"];
	$fields = call_user_func( "$tablename::publicFields", "query" );
	if( $fields instanceof ArrayObject ){ $fields = $fields->getArrayCopy(); }

	$campos = array_keys($fields);
	$campos[] = "uid_$tablename as oid";

	$db = new db();
	$sql = "SELECT ". implode(",",$campos) ." FROM $table WHERE ";

	if( isset($_REQUEST["ct"])) {
		$sql.=" ".$field." like '%".$value."%'";	
	} else {
		if( isset($_REQUEST["rel"]) && $field == "oid" ){
			$reltable = ( isset($_REQUEST["reverserel"]) ) ? DB_DATA . "." . $_REQUEST["rel"] . "_" . $tablename : $table . "_" . $_REQUEST["rel"];
			$relkey = "uid_". $_REQUEST["rel"];
			$localkey = "uid_".$tablename;
			$sql.=" $localkey IN ( SELECT $localkey FROM $reltable WHERE $relkey = ".$value.")";
		} else {
			$sql.=" ".$field." = '".$value."'";
		}
	}


	$datos = $db->query( $sql, true );
	$datos = utf8_multiple_encode($datos);

	$jsonObject = new jsonAGD();
	$jsonObject->set( $datos );
	$jsonObject->display();
?>
