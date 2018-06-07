{*
	CREA UN SUPER FORMULARIO QUE VA A CONTENER INFORMACION VARIABLE

	· $elemento -> objeto elemento a modificar
	· $comefrom -> se le pasará a public fields para saber qué información debemos mostrar, aquí usamos bigform
	{if dump($campos)}{/if}
*}

	{assign var="tpldir" value=$smarty.const.DIR_TEMPLATES}
	{assign var="comefrom" value="bigform"}
	{assign var="campos" value=$elemento->getPublicFields(true, $comefrom, $user)}
	{assign var="colums" value=2}

<form action="{$smarty.server.PHP_SELF}" class="async-form reload big">

	<div class="box-title">
		{if isset($titulo)}{$lang.$titulo}
		{else}
			{if isset($elemento)}
				{$lang.titulo_modificar}
			{else}
				{$lang.titulo_nuevo_elemento}
			{/if}
		{/if}

		<div style="float: right; margin-right: 4%;margin-top:3px;">
			<button class="btn send showload"><span><span>
			{$lang.guardar}
			</span></span></button>		
		</div>

	</div>

	{include file=$errorpath}
	{include file=$succespath}
	{include file=$infopath}



	<div class="cbox-content">
		{if isset($title)}<h1>{$title}</h1>{/if}
		<table class="agd-form" style="padding:5px">
			{assign var=lastgroup value=""}
			{assign var=i value=0}
			{foreach from=$campos item=campo key=nombre}					

				{if isset($campo.className)}
					{assign var="className" value=$campo.className}
				{else}
					{assign var="className" value=""}
				{/if}

				{assign var="display" value=$nombre}
				{if strstr($display,"[]")}
					{assign var="display" value=$display|replace:"[]":""}
				{/if}


				{if isset($campo.innerHTML)}
					{assign var="innerHTML" value=$campo.innerHTML}
					{if isset($lang.$innerHTML)}
						{assign var="innerHTML" value=$lang.$innerHTML}
					{/if}
				{elseif isset($lang.$display)}
					{assign var="innerHTML" value=$lang.$display}
				{else}
					{assign var="innerHTML" value=$display}
				{/if}

				{if $campo.group && $campo.group != $lastgroup}
					<tr class="form-group">
						<td colspan="3">{if isset($campo.groupimg)}<img src="{$campo.groupimg}">{/if}&nbsp;{$lang.opciones_de_pago} {$campo.group|capitalize:true}</td>
					</tr>
				{/if}

				<tr>
					<td class="form-colum-description" {if $campo.search}style="vertical-align: bottom;padding-bottom:0.8em;"{/if}> {$innerHTML} </td>
					<td class="form-colum-separator"></td>
					<td class="form-colum-value" style="vertical-align: middle;">
						{include file=$tpldir|cat:'form/form_parts.inc.tpl'}
					</td>
				</tr>

				{if $campo.hr}<tr><td colspan="3"><hr /></td></tr>{/if}

				{assign var=lastgroup value=$campo.group}
			{/foreach}		
		</table>
	</div>




	{if isset($smarty.get.poid)}
		<input type="hidden" name="poid" value="{$smarty.get.poid}" />	
	{else}
		{if isset($elemento)}<input type="hidden" name="poid" value="{$elemento->getUID()}" />{/if}
	{/if}
	<input type="hidden" name="send" value="1" />

</form>
