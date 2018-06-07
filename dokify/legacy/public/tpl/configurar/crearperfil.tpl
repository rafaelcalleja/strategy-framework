<div class="box-title">
	{$lang.perfil_nuevo}
</div>
<form name="crear-perfiles" action="{$smarty.server.PHP_SELF}" class="form-to-box asistente" id="crear-perfiles">
	<div style="margin: 10px;">
		{include file=$errorpath}
		{include file=$succespath}
		{include file=$infopath}
		<table>
			<tr>
				<td> Selecciona nombre Empresa </td>
				<td style="width: 2%;"></td>
				<td>
					<input type="text" name="empresa">
				</td>
			</tr>
		</table>
	</div>
	<div class="cboxButtons">
		<button class="btn"><span><span> {$lang.continuar} </span></span></button>
	</div>
	<input type="hidden" name="poid" value="{$smarty.get.poid}" />
	<input type="hidden" name="send" value="1" />
</form>

