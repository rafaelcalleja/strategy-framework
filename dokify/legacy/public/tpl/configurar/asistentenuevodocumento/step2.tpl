<div class="message highlight">
	<table>
		<tr>
			<td colspan="2">
			{$lang.tipo_documento_seleccionado}: <strong>{$nombredocumento}</strong>
			<br /><br />
			</td>
		</tr>
		<tr>
			<td colspan="2">

				{$lang.selecciona_nombre_documento}
			</td>
		</tr>
		<tr>
			<td colspan="2">	
				<input type="text" name="nombre_documento" value="{$nombredocumento}" style="width: 100%;"/>
				<br />
				{if !isset($smarty.request.m)}{/if}
			</td>
		</tr>
		<tr>
			{if isset($elemento)}
				{if $elemento instanceof agrupador}
					{assign var="agrupamiento" value=$elemento->obtenerAgrupamientoPrimario()}
					<td colspan="2"><br /><input type="hidden" name="tipo_solicitante" value="{$agrupamiento->getUserVisibleName()}" /></td>
				{/if}

				{if $elemento instanceof agrupamiento}
					<td colspan="2"><br /><input type="hidden" name="tipo_solicitante" value="{$elemento->getUserVisibleName()}" /></td>
				{/if}
			{else}
				<td>
					{$lang.selecciona_que_tipo_solicitara_documento}
				</td>
				<td>		
					<select name="tipo_solicitante">
						<option value="0" >{$lang.selecciona}</option>
						{foreach from=$solicitantes item=tiposolicitante}
							<option class="ucase" value="{$tiposolicitante->getUserVisibleName()}" 
							{if isset($smarty.request.tipo_solicitante)&&$smarty.request.tipo_solicitante==$tiposolicitante->getUserVisibleName()}selected{/if}
							>{$tiposolicitante->getUserVisibleName()}</option>
						{/foreach}
					</select>
					<br /><br />
				</td>	
			{/if}
		</tr>
		<tr>
			<td style="vertical-align: top">
				{$lang.selecciona_a_que_elemento_se_solicitara_documento}
			</td>
			<td>
				<select name="tipo_receptores[]">
					{foreach from=$solicitados item=tiposolicitado}
						<option class="ucase" value="{$tiposolicitado}"
							{if isset($smarty.request.tipo_receptores)&&in_array($tiposolicitado,$smarty.request.tipo_receptores)}selected{/if}
						>{$lang.$tiposolicitado}</option>
					{/foreach}
				</select>
			</td>
		</tr>
	</table>
</div>
<input type="hidden" name="atributes" value="1" />
<input type="hidden" name="step" value="2" />
