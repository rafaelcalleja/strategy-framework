<div class="box-title">
	{$lang.desc_descargar_exportacion}
</div>
<form name="descargar-exportacion" action="{$smarty.server.PHP_SELF}" target="async-frame" id="descargar-exportacion" enctype="multipart/form-data" method="POST">
	<div>
		<div class="cbox-content">
			{include file=$errorpath}
			{include file=$succespath}
			{include file=$infopath}
			<table class="item-list">
				<tr>
					<td colspan="3">
						{$lang.exportacion_lista_descargar}
						<br /><br />
					</td>
				</tr>
					<tr class="selected-row">
						<td colspan="2">{$export->getDownloadName()}</td>
						<td><a href="{$smarty.server.PHP_SELF}?action=dl&send=1&oid={$export->getUID()}" target="async-frame">Descargar</div></td>
					</tr>
			</table>
		</div>
	</div>
	<input type="hidden" name="poid" value="{$smarty.get.poid}" />
	<input type="hidden" name="send" value="1" />
	<div class="cboxButtons">
	</div>
</form>
