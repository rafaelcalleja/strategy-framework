{*
En uso actualmente
	-	/agd/empleado/asignarfoto.php

Variables
	· elemento -> usuario al que estamos asignando una fotografía

*}
<script type="text/javascript" src="{$resources}/js/jquery/facedetection/ccv.js"></script> 
<script type="text/javascript" src="{$resources}/js/jquery/facedetection/face.js"></script>
<script type="text/javascript" src="{$resources}/js/jquery/facedetection/jquery.facedetection.js"></script>
<div style="width:700px">
	<div class="box-title">
		{$lang.asignar_fotografia}
	</div>
	<form name="asignar-fotografia" action="{$smarty.server.PHP_SELF}" class="form-to-box" id="anexar-foto" enctype="multipart/form-data" method="POST">
		{include file=$errorpath }
		{include file=$succespath }
		{include file=$infopath }
		
		{assign var="name" value=$elemento->getUserVisibleName()}
		
			<div class="cbox-content">
				<h1>{$name}</h1>
				<table>
					<tr>
						<td>
							{$lang.seleccionar_archivo}:
							<br /><br />
						</td>
						<td style="width: 280px;">
							<div id="photoContainer" class="filecontainer" data-maxheight="{'archivo::PHOTO_HEIGHT_LIMIT_PX'|constant}">
								<button class="btn" style="white-space: nowrap" onclick="return false;"><span><span>{$lang.examinar}...</span></span></button>
								<input type="file" accept="image/*;capture=camera" size="1" filetype="image" name="archivo" id="anexar" target="#nombre-archivo-seleccionado" />
							</div>
						</td>
					</tr>
				</table>
				<hr />
				<div id="nombre-archivo-seleccionado" style="text-align: center;">
		
				</div>
			</div>
		

		<div class="cboxButtons">
			<button class="btn send"><span><span> {$lang.asignar} </span></span></button> 
		</div>
		
		<input type="hidden" name="poid" value="{$smarty.request.poid}" />
		<input type="hidden" name="m" value="{$smarty.request.m}" />
		<input type="hidden" name="send" value="1" />
	</form>
</div>