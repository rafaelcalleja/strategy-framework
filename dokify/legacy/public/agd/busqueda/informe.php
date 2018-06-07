<?php

	include_once dirname(__FILE__) . "../../../config.php";

	// $estados = array(0,1,2,3,4);
	if( !isset($estados) || !count($estados) ){
		throw new Exception("No se han especificado estados");
	}

	if( !isset($buscador) ){
		$buscador = new buscador( obtener_uid_seleccionado() );
	}

	
	if( !isset($cliente) && isset($usuario) ){
		$cliente = $usuario->getCompany();
	}

	$data = array( "empresa" => array() );
	$objetos = $buscador->getResultObjects($usuario);


	foreach($objetos as $objeto){
		$tipo = $objeto->getType();

		switch( $tipo ){
			case "empresa":
				//$empresa = $objeto;
				$data["empresa"][ $objeto->getUID() ] = array("empresa" => array($objeto) );
			break;
			case "empleado": case "maquina":
				$empresa = reset($objeto->getCompanies());
				if (!$empresa instanceof empresa) continue;
				if( !isset($data["empresa"][$empresa->getUID()]) ) $data["empresa"][$empresa->getUID()] = array();
				if( !isset($data["empresa"][$empresa->getUID()][$tipo]) ) $data["empresa"][$empresa->getUID()][$tipo] = array();

				$data["empresa"][$empresa->getUID()][$tipo][] = $objeto;

				//dump($data["empresa"][$empresa->getUID()][$objeto->getType()]);
			break;
		}	
	}

	$perfil = $usuario->obtenerPerfil();
	$locale = null;
	if ($company = $perfil->getCompany()) {
		$locale = $company->getCountry()->getLanguage();
	} else {
		$locale = Plantilla::getCurrentLocale();
	}

	$template = new Plantilla();

	ob_start();
?>

<div style="font-size: 12px">
	<?php foreach( $data["empresa"] as $uid => $modulos ){ $empresa = new empresa($uid); ?>
		<div style="border-bottom: 1px solid #000;">
			<h1> <?php $empresa->getUserVisibleName()?> </h1>
			<div>
			<?php foreach( $modulos as $modulo => $coleccion ){ ?>
				<h2> <?php print $template->getString($modulo, $locale); ?>s </h2>
				<div>
					<?php foreach( $coleccion as $i => $objeto ){ $documentos = $objeto->getDocuments(false, null, false, array("estado"=> $estados) ); ?>
						<h3> <a href="<?php print $objeto->obtenerUrlPublica($usuario); ?>"><?php print $objeto->getUserVisibleName(); ?></a> </h3>
						<div>
							<table style="font-size:12px" border="0" cellpadding="0px" cellspacing="0px">
							<?php foreach( $documentos as $i => $doc ){ 
								$requests = $doc->obtenerSolicitudDocumentos($objeto, $usuario); if(!count($requests)){ continue; }
							?>
								<tr style="background-color: <?php print($i%2)?'#FEFEFE':'#EEE'; ?>;">
									<td>
										<?php print $doc->getUserVisibleName(false, $locale); ?>
									</td>
									<td style="padding: 2px 0px 2px 10px">
										<?php foreach( $requests as $request ){
												$intestado 	= $request->getStatus();
												$atributo 	= $request->obtenerDocumentoAtributo();

												if( !in_array($intestado, $estados) ){ continue; }
												$elemento = $atributo->getElement();

												//$intestado = ( $intestado == 0 ) ? "-1" : $intestado;
												$estado = documento::status2string($intestado, $locale);
												$css = $cliente->getStyleSelectorData(".stat_$intestado");
										?>
										<span style="<?php print $css; ?>;padding: 1px; margin:1px;" title="<?php print $elemento->getType(); ?> <?php print $elemento->getUserVisibleName(); ?>"><?php print $estado; ?></span>
									<?php } ?>
									</td>
								</tr>
							<?php } ?>
							</table><br />
						</div>
					<?php } ?>
				</div>
			<?php }?>
			</div>
		</div>
	<?php } ?>
</div>

<?php
	$buffer = ob_get_clean();
	if(realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME'])){
		echo $buffer;
	} else {
		return $buffer;
	}
?>
