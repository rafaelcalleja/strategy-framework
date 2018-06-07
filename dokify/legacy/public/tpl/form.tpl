{*
	CREA FORMULARIOS A PARTIR DE OBJETOS O ARRAY DE CAMPOS

	· $extraOptions = array( "lang" => html attr );
	· $elemento -> elemento a modificar
	· $campos -> array para crear nuevo elemento...
	· $titulo -> titulo principal
	· $title -> titulo extra
	· $boton -> el texto del boton
	· $comefrom -> se le pasará a public fields para editar
*}

{if isset($elemento) && !isset($campos)}
	{if !isset($comefrom) }
		{assign var="comefrom" value="edit"}
	{/if}
	{assign var="campos" value=$elemento->getPublicFields(true, $comefrom, $user)}

{/if}
{if isset($extraOptions) && is_traversable($extraOptions)}
	{if is_array($campos)}
		{assign var=campos value=$campos|array_merge:$extraOptions}
	{elseif $campos instanceof FieldList}
		{assign var=campos value=$campos->merge($extraOptions)}
	{/if}
{/if}

{if isset($request) && isset($campos) }
	{assign var="campos" value="merge_values_and_fields"|call_user_func:$campos:$request}
{/if}

<div class="box-title">
	{if isset($titulo)}{$lang.$titulo|default:$titulo}
	{else}
		{if isset($elemento)}
			{$lang.titulo_modificar}
		{else}
			{$lang.titulo_nuevo_elemento}
		{/if}
	{/if}
</div>
<form name="elemento-form-new" action="{$smarty.server.PHP_SELF}" {if isset($className)}class="{$className}"{else}class="form-to-box asistente"{/if} method="{$smarty.server.REQUEST_METHOD}" id="elemento-form-new" {if isset($width)}style="width: {$width};"{/if}>
	{include file=$errorpath}
	{include file=$succespath}
	{include file=$infopath}
	<div class="cbox-content">
		{if isset($title)}<h1>{$title}</h1>{/if}
		{if isset($notify) && strlen($notify)}
			<div class="notifyAlert">
				{$notify}
			</div>
		{/if}
		{if $tip}
			<div style="text-align: center; padding:0 0 10px;">
				<img src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/information.png" style="vertical-align:middle" /> {if $tip.href}<a href="{$tip.href}" target="{$tip.target|default:'_blank'}">{/if}{$lang[$tip.innerHTML]}{if $tip.href}</a>{/if}
			</div>
		{/if}

		{include file=$tpldir|cat:'form/form_table.inc.tpl'}

		{if isset($note)}
			<hr />
			{$lang.$note|default:$note}
		{/if}
	</div>
	<div class="cboxButtons">
		{if $boton !== false}
			<button class="btn{if isset($notifyConfirm)} confirm{/if}" {if isset($notifyConfirm) && strlen($notifyConfirm)}data-confirm="{$notifyConfirm}"{/if} type="submit"><span><span>
				{if isset($boton)}
					{$lang.$boton}
				{else}
					<img src="{$resources}/img/famfam/add.png"> {$lang.guardar}
				{/if}
			</span></span></button>
		{/if}

		{include file=$tpldir|cat:'button-list.inc.tpl'}
		<div style="clear:both"></div>
	</div>
	{if isset($smarty.get.config)}<input type="hidden" name="config" value="{$smarty.get.config}" />{/if}
	{if isset($smarty.get.m)}<input type="hidden" name="m" value="{$smarty.get.m}" />{/if}
	{if isset($smarty.get.comefrom)}<input type="hidden" name="comefrom" value="{$smarty.get.comefrom}" />{/if}
	{if isset($smarty.get.o)}<input type="hidden" name="o" value="{$smarty.get.o}" />{/if}
	{if isset($smarty.get.oid)}<input type="hidden" name="oid" value="{$smarty.get.oid}" />{/if}
	{if isset($smarty.get.ref)}<input type="hidden" name="ref" value="{$smarty.get.ref}" />{/if}
	{if isset($smarty.get.return)}<input type="hidden" name="return" value="{$smarty.get.return}" />{/if}
	{if isset($smarty.get.field)}<input type="hidden" name="field" value="{$smarty.get.field}" />{/if}
	{if isset($smarty.request.frameopen)}<input type="hidden" name="frameopen" value="{$smarty.request.frameopen}" />{/if}
	{if isset($smarty.request.edit)}<input type="hidden" name="edit" value="{$smarty.request.edit}" />{/if}
	{if isset($smarty.request.context)}<input type="hidden" name="context" value="{$smarty.request.context}" />{/if}
	{if isset($smarty.request.comefrom)}<input type="hidden" name="comefrom" value="{$smarty.request.comefrom}" />{/if}
	{if isset($smarty.request.req)}<input type="hidden" name="req" value="{$smarty.request.req}" />{/if}
	{if isset($smarty.get.poid)}
		<input type="hidden" name="poid" value="{$smarty.get.poid}" />
	{else}
		{if isset($elemento)}<input type="hidden" name="poid" value="{$elemento->getUID()}" />{/if}
	{/if}

	{if isset($smarty.request.selected)}
		{foreach from=$smarty.request.selected item=seleccionado}
			<input type="hidden" name="selected[]" value="{$seleccionado}" />
		{/foreach}
	{/if}


	<input type="hidden" name="send" value="1" />
	{if isset($data)}
		{foreach from=$data item=value key=name}
			<input type="hidden" name="{$name}" value="{$value}" />
		{/foreach}
	{/if}
</form>
