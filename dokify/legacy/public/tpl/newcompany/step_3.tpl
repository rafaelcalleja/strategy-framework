<div style="float:left;width:100%;padding:10px">
	<form action="?step=4" method="post">
		<h2>{$lang.title_form_step_three}</h2>
		<br />

		<div class="containerHr">
				{$lang.signup_form_definition_cif|sprintf:$lang.cif:$inviterEmail}
			</div>
		<table class="form">
			<tr>
				<td>{$lang.cif}<br/>{if isset($error.cif)}<span style="color:red;font-size:14px">{$lang.signup_form_error_cif}<span>{/if}</td>
				<td><input type="text" name="cif"  {if isset($error.cif)}class="error"{/if} {if isset($data.cif)}value="{$data.cif}"{/if} /></td>
			</tr>
			<tr>
				<td>{$lang.nombre_empresa}<br/>{if isset($error.nombre_empresa)}<span style="color:red;font-size:14px">{$lang.signup_form_error_invalido}<span>{/if}</td>
				<td><input type="text" name="nombre_empresa" {if isset($error.nombre_empresa)}class="error"{/if} {if isset($data.nombre_empresa)}value="{$data.nombre_empresa}"{/if}/></td>
			</tr>
			<tr>
				<td>
					{$lang.tipo_sociedad}
					<br/>{if isset($error.kind)}<span style="color:red;font-size:14px">{$lang.selecciona_tipo_sociedad}<span>{/if}</td>
				<td>
					<select data-placeholder="" name="kind" id="kind" class="chzn-select" tabindex="2">
				        <option value="-" selected>{$lang.tipo_sociedad}</option>
						{foreach from=$kinds item=value key=clave}
					   		<option value="{$clave}" {if isset($data.kind) && is_numeric($data.kind) && $data.kind==$clave}selected{/if}>{$value}</option>
				     	{/foreach}
				    </select>
				</td>
			</tr>
			<tr>
				<td>
					{$lang.servicio_prevencion}
					<br/>
					{if isset($error.prevention_service)}<span style="color:red;font-size:14px">{$lang.servicio_prevencion}<span>{/if}</td>
				</td>
				<td>
					<select data-placeholder="" name="prevention_service" id="prevention_service" class="chzn-select" tabindex="2">
				        <option value="">{$lang.servicio_prevencion}</option>
						{foreach from=$preventionServices item=prevention key=i}
					   		<option value="{$prevention.uid}" {if isset($data.prevention_service) && $data.prevention_service==$prevention.uid}selected{/if}>{$prevention.name}</option>
				     	{/foreach}
				    </select>
				</td>
			</tr>
			<tr>
				<td>{$lang.nombre_comercial}<br/>{if isset($error.nombre_comercial)}<span style="color:red;font-size:14px">{$lang.signup_form_error_invalido}<span>{/if}</td>
				<td><input type="text" name="nombre_comercial" {if isset($error.nombre_comercial)}class="error"{/if} {if isset($data.nombre_comercial)}value="{$data.nombre_comercial}"{/if}/></td></tr>
			{if count($tiposEmpresa) && is_traversable($tiposEmpresa)}
				<tr>
					<td>{$lang.tipoempresa}
						<br />
						<span style="color:black;font-size:11px">(para {$companySender->getUserVisibleName()})</span>
						<br/>{if isset($error.tipo_empresa)}<span style="color:red;font-size:14px">{$lang.selecciona_tipo_empresa}<span>{/if}</td>
					<td>
						<select data-placeholder="" name="tipo_empresa" id="tipo_empresa" class="chzn-select" tabindex="2">
					        <option value="-" select>{$lang.tipoempresa}</option>
							{foreach from=$tiposEmpresa item=object key=valor}
						   		<option value="{$object->getUID()}" {if isset($data.tipo_empresa) && isset($object) && $data.tipo_empresa==$object->getUID()} selected {/if}>{$object->getUserVisibleName()}</option>
					     	{/foreach}         
					    </select>
					</td>
				</tr>
			{/if}
			<tr>
				<td>{$lang.representante_legal}<br/><span style="color:black;font-size:11px">* {$lang.form_optional_field}<span></td>
				<td><input type="text" name="representante_legal" {if isset($error.representante_legal)}class="error"{/if} {if isset($data.representante_legal)}value="{$data.representante_legal}"{/if}  /></td>
			</tr>
		</table>

		<br />
		<div style="text-align:right">
			<input type="hidden" name="send" value="1" />
			<button class="continue">{$lang.siguiente}</button>
		</div>
	</form>
</div>








