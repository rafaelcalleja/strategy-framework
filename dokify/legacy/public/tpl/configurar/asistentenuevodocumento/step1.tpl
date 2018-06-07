<div class="message highlight">
	<table>
		<tr>
			<td>
			{$lang.selecciona_tipo_documento_a_crear}: <br /><br />
			</td>
		</tr>
		<tr>
			<td>
				<select name="tipo_documento">
					<option>{$lang.selecciona}</option>
					{foreach from=$documentos item=documento}
						<option value="{$documento.uid_documento}">{$documento.nombre}</option>
					{/foreach}
				</select>
			</td>
		</tr>
	</table>
</div>
<input type="hidden" name="step" value="1" />
