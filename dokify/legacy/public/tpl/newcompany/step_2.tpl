<div style="float:left;width:100%;padding:10px">
	<form action="?step=3" method="post">
		<h2>{$lang.title_form_step_two}</h2>
		<br />
		<table class="form">
			<tr>
				<td>{$lang.pais}<br/>{if isset($error.uid_pais)}<span style="color:red;font-size:14px">{$lang.signup_form_error_pais}<span>{/if}</td>
				<td>
					{assign var=paises value="pais::obtenerTodos"|call_user_func}
					
					
					<select name="uid_pais" id="uid_pais" class="chzn-select" data-defaultcountry="{'pais::SPAIN_CODE'|constant}" data-reference=".table-element-hidden">
						<option value="-" select>{$lang.pais}</option>
						{foreach from=$paises item=object key=valor}
					       	<option value="{$object->getUID()}" {if isset($data.uid_pais) && isset($object) && $data.uid_pais==$object->getUID()} selected {/if}>{$object->getUserVisibleName()}</option>
					     {/foreach}
				    </select>
				</td>



			</tr>
			<tr class="table-element-hidden {if !isset($data.uid_pais) || (isset($data.uid_pais) && $data.uid_pais!=174)}hidden{/if}">
				<td>{$lang.provincia}<br/>{if isset($error.uid_provincia)}<span style="color:red;font-size:14px">{$lang.signup_form_error_provincia}<span>{/if}</td>
				<td>
					{assign var=provincias value="provincia::obtenerTodos"|call_user_func}
					<select name="uid_provincia" id="uid_provincia" class="chzn-select" data-reference="#container-uid-municipio" data-refresh="#uid_municipio">
						<option value="-" select>Provincia</option>
					    	{foreach from=$provincias item=object key=valor}
					       		<option  value="{$object->getUID()}" {if isset($data.uid_provincia) && isset($object) && $data.uid_provincia==$object->getUID()} selected {/if}>{$object->getUserVisibleName()}</option>
					     	{/foreach}
				    </select>
				</td>
				
			</tr>

			{if $data.uid_provincia && $data.uid_provincia!="-"}
				{assign var=municipios value="municipio::obtenerPorProvincia"|call_user_func:$data.uid_provincia}
			{/if}

			<tr class="table-element-hidden {if !isset($data.uid_pais) || (isset($data.uid_pais) && $data.uid_pais!=174)}hidden{/if}">
				<td>{$lang.municipio}<br/>
					<img id="loading-ajax" src="{$smarty.const.RESOURCES_DOMAIN}/img/common/ajax-loader.gif">
					<span id="loading-ajax-error">{$lang.signup_form_error_loading}</span>
					{if isset($error.uid_municipio)}<span style="color:red;font-size:14px">{$lang.signup_form_error_municipio}<span>{/if}</td>
				<td id="container-uid-municipio">
					<select name="uid_municipio" id="uid_municipio" class="chzn-select">
						{if !isset($data.uid_municipio)}<option value="-" select>{$lang.signup_form_error_provincia}</option>{else}{$lang.municipio}{/if}
						 {if isset($data.uid_municipio)}
					    	{foreach from=$municipios item=object key=valor}
					       		<option value="{$object->getUID()}" {if isset($data.uid_municipio) && isset($object) && $data.uid_municipio==$object->getUID()} selected {/if}>{$object->getUserVisibleName()}</option>
					     	{/foreach}
					      {/if}
				    </select>
				</td>
			</tr>
			<tr>
				<td>{$lang.direccion}<br/>{if isset($error.direccion)}<span style="color:red;font-size:14px">{$lang.signup_form_error_direccion}<span>{/if}</td>
				 <td><input name="direccion" {if isset($error.direccion)}class="error"{/if} {if isset($data.direccion)} value="{$data.direccion}"{/if} type="text" /></td>
				</tr>
			<tr>
				<td>{$lang.cp}<br/>{if isset($error.cp)}<span style="color:red;font-size:14px">{$lang.signup_form_error_cp}<span>{/if}</td>
				<td><input name="cp" {if isset($error.cp)}class="error"{/if} {if isset($data.cp)} value="{$data.cp}"{/if} type="text" /></td>
			</tr>
		</table>

		<br />
		<div style="text-align:right">
			<input type="hidden" name="send" value="1" />
			<button class="continue">{$lang.siguiente}</button>
		</div>
	</form>
</div>