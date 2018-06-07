<div class="box-title" style="margin:0px">
	<span title="{$elemento->getUserVisibleName()}">{$elemento->getUserVisibleName()|truncate:40:'...'}</span>
	{*
	{if isset($referencia)}
		{assign var=tipo value=$referencia->getType()}
		| {$lang.$tipo} <span title="{$referencia->getUserVisibleName()}">{$referencia->getUserVisibleName()|truncate:40:'...'}</span>
	{/if}
	*}
</div>
<form name="filepreview-form" action="{$return}" class="form-to-box" id="filepreview-form">
	<div>
		{include file=$tpldir|cat:'tabla_validar.inc.tpl'}
	</div>
	
	<div style="min-width:960px;height:480px">
		<iframe id="framePreview" src="{$url}"></iframe>
	</div>
	<div class="cboxButtons" style="margin-top:0px;">
		{if isset($referencia) && ( $referencia instanceof empleado || $referencia instanceof maquina ) }
		<div style="float: left;font-size: 18px; font-family: Lucida Grande,sans-serif">
			<span class="light">{$lang.empresa}:</span>
			<strong>
			{assign var=empresas value=$referencia->getCompanies()}
			{foreach from=$empresas item=empresa}
				{$empresa->getUserVisibleName()} &nbsp;&nbsp;
			{/foreach}
			</strong>
		</div>
		{/if}
		{include file=$tpldir|cat:'button-list.inc.tpl'}
		<button class="btn box-it" href="{$return}">
			<span><span> {$lang.volver} </span></span>
		</button>
		<input type="hidden" name="send" value="1" />
	</div>
</form>
