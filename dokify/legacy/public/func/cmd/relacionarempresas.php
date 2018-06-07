<?php
	include dirname(__FILE__) . "/../../config.php";

	if( isset($_SERVER["argv"]) ){
		$ARGV = $_SERVER["argv"];
		if( isset($ARGV[1]) && is_readable($ARGV[1]) ){

			$default = ( isset($ARGV[2]) ) ? $ARGV[2] : NULL;

			$tmptabla = "tmp_relacion_import_".uniqid();
			$temporal = DB_TMP .".$tmptabla";

			$db = db::singleton();
			$reader = new dataReader($tmptabla , $ARGV[1], archivo::getExtension($ARGV[1]) );


			if(1){
				foreach($reader->getAsArray() as $i => $linea){
					if(!$i) continue; //los titulos
					$UIDSuperior = $linea[0];
					$UIDInferior = $linea[1];

					$sql = "SELECT count(uid_empresa_relacion) as c, uid_empresa_superior as superior FROM ". TABLE_EMPRESA ."_relacion WHERE uid_empresa_inferior = {$UIDInferior}";

					$data = $db->query($sql, 0, "*");

					$num = $data["c"];
					switch($num){
						case 0:
							echo "La empresa {$UIDInferior} no tiene relación superior actualmente\n";
						break;
						case 1:
							$superior = $data["superior"];
							if( $UIDSuperior != $superior ){
								$update = "UPDATE ".  TABLE_EMPRESA . "_relacion SET uid_empresa_superior = {$UIDSuperior} WHERE uid_empresa_inferior = {$UIDInferior}";
								//echo $update . "\n";
								if( $db->query($update) ){
									echo "Se ha movido la empresa {$UIDInferior} de la {$superior} a la {$UIDSuperior}\n";
								}
							} else {
								echo "No se mueve la empresa {$UIDInferior} de la {$superior} por ser la misma\n";
							}

							//die("La empresa {$UIDInferior} tiene solo una empresa superior\n");
						break;
						default:
							if( $default ){
								$superior = $data["superior"];
								if( $UIDSuperior != $superior ){
									$update = "UPDATE ".  TABLE_EMPRESA . "_relacion SET uid_empresa_superior = {$UIDSuperior} WHERE uid_empresa_inferior = {$UIDInferior} AND uid_empresa_superior = {$default}";
									//echo $update . "\n";
									if( $db->query($update) ){
										echo "Se ha movido la empresa {$UIDInferior} de la {$superior} a la {$UIDSuperior}\n";
									}
								} else {
									$get = "SELECT count(uid_empresa_superior) FROM ".  TABLE_EMPRESA . "_relacion WHERE uid_empresa_superior = {$default} AND uid_empresa_inferior = {$UIDInferior}"; 
									$exists = $db->query($sql, 0, 0);
			
									if( $exists ){
										$delete = "DELETE FROM ".  TABLE_EMPRESA . "_relacion WHERE uid_empresa_superior = {$default} AND uid_empresa_inferior = {$UIDInferior}";
										if( $db->query($delete) ){
											echo "Se ha eliminado la relación entre {$UIDInferior} y {$default} al solo venir en la lista su relación con {$UIDSuperior}\n";
										}
									} else {
										echo "No se mueve la empresa {$UIDInferior} de la {$superior} por ser la misma\n";
									}
								}
							} else {
								echo "No se mueve la empresa {$UIDInferior} de la {$superior} a la {$UIDSuperior} por ser tener mas relaciones\n";
							}
						break;
					}
				}
			}

			if( $reader->cargar(true) ){
				$sql = "
					SELECT superior, inferior FROM $temporal t 
					LEFT JOIN agd_data.empresa_relacion er 
					ON er.uid_empresa_superior = t.superior 
					AND er.uid_empresa_inferior = t.inferior 
					WHERE er.uid_empresa_relacion IS NULL
				";

				$data = $db->query($sql, true);
				foreach( $data as $line ){
					$sql = "INSERT INTO agd_data.empresa_relacion (uid_empresa_superior, uid_empresa_inferior) VALUES ({$line['superior']}, {$line['inferior']})";
					if( !$db->query($sql) ){
						echo "Error al crear la relación {$line['superior']} - {$line['inferior']}\n"; dump($db);
					}
				}
			}
		} else {
			die("Debes especificar el CSV con la relación de empresas");
		}
	}
?>
