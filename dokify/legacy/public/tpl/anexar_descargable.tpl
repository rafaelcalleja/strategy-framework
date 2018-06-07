{*
Descripcion
	Plantilla para su uso en modalbox, incluye referencias a error, succes e info
	Es siminar a <a href='?tpl=anexar'>anexar</a> pero se usa sin solicitantes si no con el elemento de origen como filtro

En uso actualmente
	-	/agd/configurar/documento/anexar.php

Variables
	· fechas = array() - selectores de fechas
	· oid = true -> muestra el campo hidden de oid.referenciado en el archivo anexar.php del plugin filestorage (DE QUIEN ES ESTO?? - JOSE )
	· titulo -> mostrar referencia de la accion
	· htmlafter -> si queremos mostrar alguna informacion extra
	· htmlbefore -> si queremos mostrar alguna informacion extra

*}
<div class="box-title">
	{$lang.$customTitle|default:$lang.anexar_archivo}
</div>
<form name="anexar-documento" action="{$smarty.server.PHP_SELF}" class="form-to-box" id="anexar-documento" enctype="multipart/form-data" method="POST" {if $pass}data-pass="true"{/if}>
	<div style="width: 590px;">
		{include file=$errorpath}
		{include file=$succespath}
		{include file=$infopath}
		<div class="cbox-content">
			{if isset($titulo)}<h1>{$titulo}</h1>{/if}
			<table>
				{if isset($htmlbefore)}
					<tr>
						<td colspan="2"></td>
					</tr>
					<tr>
						<td colspan="2">
							{$htmlbefore}
						</td>
					</tr>
				{/if}
				<tr>
					<td >
						{$lang.seleccionar_archivo}
						<br /><br />
					</td>
					<td style="width: 280px;">
						<div class="filecontainer">
							<!--<a href="" style="white-space: nowrap">Seleccionar archivo</a>-->
							<button class="btn" style="white-space: nowrap" onclick="return false;"><span><span>Examinar...</span></span></button>
							<input type="file" size=1 name="archivo" {if isset($file)}complete="true"{/if}  id="anexar" target="#nombre-archivo-seleccionado" />
						</div>
					</td>
				</tr>
				<tr>
					<td colspan="2">
						Archivo seleccionado:
						<strong id="nombre-archivo-seleccionado">
								{if isset($file)}
									<a title="{$file.name} ({if print round($file.size/1024)}{/if}Kb)" href="getuploaded.php?action=dl" target="async-frame">{$file.name} <i>({$file.type})</i></a>
								{/if}
						</strong>


						<br /><br />
					</td>
				</tr>
				{if isset($fechas) && is_array($fechas)}
					{foreach from=$fechas item=fecha key=key}
						<tr>
							<td>
								{$lang.$fecha}:
								<br /><br />
							</td>
							<td>
								 <input type="text" name="{$fecha}" accept="image/*;capture=camera" class="datepicker" size="8" onchange="return false;" value="" matche="^([0][1-9]|[12][0-9]|3[01])(/|-)(0[1-9]|1[012])\2(\d{4})$"/>
							</td>
						</tr>
					{/foreach}
				{/if}
				{if isset($campos)}{include file=$tpldir|cat:'form/form_table.inc.tpl'}{/if}
				
				{if isset($inputs) && is_array($inputs)}
					{foreach from=$inputs item=input key=key}
						{assign var="html" value=$input.innerHTML}
						<tr>
							<td>
								{$lang.$html|default:"INPUT"}:
								<br /><br />
							</td>
							<td>
								{if $input.type == 'select'}
 									<select name="{$input.name}">
 										{foreach from=$input.options item=option}
 											<option value="{$option.value}">{$option.innerHTML}</option>
 										{/foreach}
 									</select>
								{else}
								 <input type="{$input.type|default:'text'}" name="{$input.name}" {if isset($input.checked)}checked="true"{/if} {if isset($input.blank)&&$input.blank===false}blank="false"{/if} {if isset($input.value)}value="{$input.value}"{/if} />
								 {/if}
							</td>
						</tr>
					{/foreach}
				{/if}
				{if isset($htmlafter)}
					<tr>
						<td colspan="2"><br /></td>
					</tr>
					<tr class="separator">

						<td colspan="2">
							<br/>
							{$lang.info_upload_excel}
							<b>{$htmlafter}</b>
						</td>
					</tr>
				{/if}
			</table>
		</div>
	</div>

	{if isset($oid)}<input type="hidden" name="oid" value="{$smarty.request.oid}" />{/if}
	{if isset($smarty.request.m)}<input type="hidden" name="m" value="{$smarty.request.m}" />{/if}
	{if isset($smarty.request.action)}<input type="hidden" name="action" value="{$smarty.request.action}" />{/if}
	<input type="hidden" name="poid" value="{$smarty.request.poid}" />
	<input type="hidden" name="send" value="1" />
	<div class="cboxButtons">
		{include file=$tpldir|cat:'button-list.inc.tpl'}
		{if isset($smarty.request.return)}
			<div style="float:left">
				<a class="btn box-it" href="{$smarty.request.return}"><span><span> <img src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/arrow_undo.png" /> {$lang.volver} </span></span></a> 
			</div>
		{/if}
		{if $boton !== false}
			<button class="btn send"><span><span> <img src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/attach.png" /> {$lang.anexar} </span></span></button> 
		{/if}
		<div class="clear"></div>
	</div>
</form>
