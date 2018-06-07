{*
	Util para crear listar los permisos relacionados entre un usuario y un objeto de la aplciacion

	· $permisosactivos array devuelto por obtenerOpcionesObjeto de la clase perfil del usuario a modificar
	· $permisos array devuelto por obtenerOpcionesObjeto de la clase perfil del usuario que modifica
	· $objeto
	· $perfil
	· $usuario
*}

<div class="box-title">
	{$lang.$title|default:"Permisos especificos"} - {$usuario->getUserName()} @ {$perfil->getUserVisibleName()|ireplace:"perfil":""}

	{if count($permisos)>1}
		{assign var="counter" value=0}
		<div class="tabs">
		{foreach from=$permisos key=tipopermiso item=listapermisos}	
			<div class="box-tab ucase {if !$counter}selected{/if}" rel="#tab-{$tipopermiso}">{$lang.$tipopermiso|default:$tipopermiso}</div>
			{assign var="counter" value=$counter+1}
		{/foreach}
		</div>
	{/if}
</div>
<form name="tabs-form" action="{$smarty.server.PHP_SELF}" class="form-to-box" id="tabs-form" method="GET" style="width: 650px">
	{include file=$errorpath}
	{include file=$succespath}
	{include file=$infopath}
	<div class="cbox-content">
		<div id="tabs-content">
			{assign var="counter" value=0}
			{foreach from=$permisos key=tipopermiso item=listapermisos}	
				{assign var=activos value=$permisosactivos.$tipopermiso}
				<div id="tab-{$tipopermiso}"  {if $counter}style="display: none"{/if} >
					<h1>{$objeto->getUserVisibleName()}</h1>
					<p style="width: 500px;"> {$lang.aviso_permisos_especificos}</p>
					<a class="btn checkall link" style="float: right; padding: 0 0 0 15px;" target="#tab-{$tipopermiso}">{$lang.seleccionar_todo} </a>
					<hr />

					{if !$counter}
						{assign var="$tipo" value="TIPO_ESPECIFICO"}
						{assign var=roles value="rol::obtenerRolesGenericos"|call_user_func:$tipo}
						{$lang.aplicar_permisos_predefinidos}
						<select id="lista-roles" name="rol" style="font-size:11px;">
							<option> {$lang.seleccionar_rol} </option>
							{foreach from=$roles item=rol key=indice}
								<option value="{$rol->getUID()}" >{$rol->getUserVisibleName()}</option>
							{/foreach}
						</select>
						<button class="btn sendinput" href="{$smarty.server.PHP_SELF}?action=rol&m={$smarty.get.m}&poid={$smarty.get.poid}&o={$smarty.get.o}" target="#lista-roles"><span><span>{$lang.asignar}</span></span></button>
						<hr />
					{/if}

					<table class="item-list">
					{foreach from=$listapermisos item=permiso key=oid}
						{assign var=innerHTML value=$permiso.string}
						<tr>
							<td> <img src="{$resources}{$permiso.icono}" height="16px"/> {$lang.$innerHTML}	</td>
							<td>
								<input type="checkbox" name="accion[]" value="{$oid}" class="line-check" {if $activos && isset($activos.$oid)} checked {/if}/>
							</td>
						</tr>
					{/foreach}
					</table>
				</div>
				{assign var="counter" value=$counter+1}
			{/foreach}
		</div>
	</div>
	<div class="cboxButtons">
		<button class="btn checkall" target="form#tabs-form"><span><span> {$lang.seleccionar_todo} </span></span></button>
		<button class="btn"><span><span>{$lang.guardar}</span></span></button>
		{include file=$tpldir|cat:'button-list.inc.tpl'}
	</div>
	<input type="hidden" name="send" value="1" />
	{if isset($smarty.get.m)}<input type="hidden" name="m" value="{$smarty.get.m}" />{/if}
	{if isset($smarty.get.o)}<input type="hidden" name="o" value="{$smarty.get.o}" />{/if}
	{if isset($smarty.get.oid)}<input type="hidden" name="oid" value="{$smarty.get.oid}" />{/if}
	{if isset($smarty.get.poid)}<input type="hidden" name="poid" value="{$smarty.get.poid}" />{/if}
</form>

