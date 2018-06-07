{*
Variables plugin

*}
<div class="box-title">
	{$lang.avisos}
</div>
<form name="avisos-cliente" action="{$smarty.server.PHP_SELF}" class="form-to-box" id="avisos-cliente" enctype="multipart/form-data" method="POST">
	<div style="width: 730px"">
		{include file=$errorpath}
		{include file=$succespath}
		{include file=$infopath}
		<div class="cbox-content">
			<table class="item-list">
				<tr>
					<td> Caducidad </td>
					<td style="width: 5px">  </td>
					<td> <a class="to-iframe-box" name="plantilla-caducidad" href="{$smarty.server.PHP_SELF}?poid={$smarty.get.poid}&plantilla=caducidad">Enviar este aviso</a> </td>
				</tr>
			</table>
		</div>
	</div>

	<input type="hidden" name="poid" value="{$smarty.get.poid}" />
	<input type="hidden" name="send" value="1" />
	<div class="cboxButtons">
		{*<button class="btn" onclick="this.disabled='true'"><span><span> {$lang.enviar} </span></span></button> *}
	</div>
</form>
