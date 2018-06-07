<?php
	$dominio = $_REQUEST["d"];
	if( strpos($dominio,"afianza.net") !== false ) {
		$goto = "http://".$dominio."/agd/#buscar.php?p=0&q=";
	} else {
		$goto = "http://agd.afianza.net/agd/#buscar.php?p=0&q=";
	}
	
?>

<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
		<link type="text/css" rel="stylesheet" href="http://estatico.afianza.net/css/style.css.php">

		<script>
			function buscar() {
				var ira="<?php echo $goto ?>";var strings=new Array(),form=document.getElementById("helpsearch"),elements=form.elements,len=elements.length;while(len--){element=elements[len];if(element.value!=""){if(element.name=="texto"){str=encodeURIComponent(element.value)}else{str=encodeURIComponent(element.name+":"+element.value)}strings.push(str)}}if(!strings.length){alert("Seleccione algun criterio de busqueda");return false}var url=ira+strings.join("+");parent.location.href=url;return false;						
			}
		</script>
		

	</head>
	<body style="margin:0px">
		<form id="helpsearch" name="helpsearch" style='height:100%' class="agd-form" method="GET" onsubmit="buscar();return false;">
			<div class="cbox-content" style="height:96px;paddin-top:3px">
				<table border="0" cellpadding="0" cellspacing="0" style="width:100%">
					<tr>
						<td class="form-colum-description"> Tipo </td>
						<td class="form-colum-separator"></td>
						<td style="vertical-align: middle;" class="form-colum-value">
							<select name="tipo" style="width:50%">
								<option value="">-- Seleccione --</option>
								<option value="empresa">Empresa</option>
								<option value="empleado">Empleado</option>
								<option value="usuario">Usuario</option>
								<option value="maquina">Maquina</option>
							</select>
						</td>
					</tr>
					<tr>
						<td class="form-colum-description"> Documentos </td>
						<td class="form-colum-separator"></td>
						<td style="vertical-align: middle;" class="form-colum-value">
							<select name="docs" style="width:50%">
								<option value="">-- Seleccione --</option>
								<option value="caducados">Caducados</option>
								<option value="validos">Validos</option>
								<option value="sin-anexar">Sin-Anexar</option>
								<option value="anulados">Anulados</option>
								<option value="pendientes">Pendintes</option>
							</select>
						</td>
					</tr>
					<tr>
						<td class="form-colum-description"> Asignado </td>
						<td class="form-colum-separator"></td>
						<td style="vertical-align: middle;" class="form-colum-value">
							<input type="text" rel="blank" class="" name="asignado">
						</td>
					</tr>
					<tr>
						<td class="form-colum-description"> Texto </td>
						<td class="form-colum-separator"></td>
						<td style="vertical-align: middle;" class="form-colum-value">
							<input type="text" rel="blank" class="" name="texto">
						</td>
					</tr>
				</table>
			</div>

			<div class="cboxButtons" style="height:25px">
				<button class="btn"><span><span>Buscar</span></span></button>
			</div>

		</form>
	</body>
</html>
