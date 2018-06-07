<div class="box-title">{$lang.sugerencias}</div>
<form action="{$smarty.server.PHP_SELF}" method="POST" class="form-to-box">
	{include file=$errorpath}
	{include file=$succespath}
	{include file=$infopath}
	<div class="cbox-content" style="width: 550px">
		{$lang.sugerencias_texto}
		<br />
		<br />
		<table>
			<tr> 
				<td>{$lang.sugerencias_pregunta}</td>
				<td class="middle-td"></td>
				<td>
					<select name="tipo" class="wrap-width">
						<option> {$lang.sugerencias_respuesta_sugerencias} </option>
						<option> {$lang.sugerencias_respuesta_mejoras} </option>
						<option> {$lang.sugerencias_respuesta_problemas} </option>
					</select>
				</td>
			</tr>
			<tr>
				<td colspan="3">
					<textarea name="texto" class="wrap-width margenize"></textarea>
				</td>
			</tr>
			<tr> 
				<td colspan="3">
					<span class="ucase">{$empresaCliente->getUserVisibleName()}</span> <input type="checkbox" name="cliente" /> 
					<span>{$lang.servicio_atencion_cliente}</span> <input type="checkbox" name="agd" checked />
				</td>
			</tr>
		</table>
	</div>
	<div class="cboxButtons">
		<button class="btn"><span><span>{$lang.enviar}</span></span></button>
	</div>
	<input type="hidden" name="send" value="1" />
</form>
