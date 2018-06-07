<div class="box-title">
	{assign var=tipo value=$elemento->getType()}
	{$lang.validar_archivo} · {$lang.$tipo} {$elemento->getUserVisibleName()}
</div>
<form name="validar-documento" action="{$smarty.server.PHP_SELF}" class="form-to-box agd-form" id="validar-documento" method="GET" style="width: 980px">
	<div>
		{include file=$errorpath}
		{include file=$succespath}
		{include file=$infopath}

		{assign var=totalAnexos value=$documento->obtenerAnexos($elemento, $user)}

		<div class="cbox-content">
			<h1>{$documento->getUserVisibleName()}</h1>

			
			<div style="padding: 0 0 1em">
				{$lang.validar_texto}
				{if $selectedRequest && $totalAnexos|count > 1}
					- <span class="red">{$lang.mostrando_solicitud_seleccionada}.</span> <a href="validar.php?m={$elemento->getModuleName()}&poid={$documento->getUID()}&o={$elemento->getUID()}&validate=validar" class="box-it">{$lang.ver_todas}</a>
				{/if}
			</div>
		
		</div>
		<div>
			{include file=$tpldir|cat:'tabla_validar.inc.tpl'}
		</div>
		{if $smarty.request.url}
		<div style="min-width:960px;height:480px;border-top:1px solid #000">
			<iframe id="framePreview" src="{$smarty.request.url}"></iframe>
		</div>
		{/if}
	</div>

	<input type="hidden" name="poid" value="{$smarty.get.poid}" />
	<input type="hidden" name="m" value="{$smarty.get.m}" />
	<input type="hidden" name="o" value="{$smarty.get.o}" />
	<input type="hidden" name="send" value="1" />
	<div class="cboxButtons">
		{assign var=actions value=$user->getAvailableOptionsForModule("documento", "enviar")}
		{if $action=reset($actions)}
			<button class="btn" href="{$action.href}?m={$smarty.get.m}&poid={$smarty.get.poid}&o={$smarty.get.o}&oid={$smarty.get.oid}"><span><span> <img src="{$action.icono}" /> {$lang.enviar} </span></span></button> 
		{/if}
		{assign var=actions value=$user->getAvailableOptionsForModule($documento, "anular")}
		{if $action=reset($actions)}
			<button class="btn box-it" href="validar.php?poid={$smarty.get.poid}&m={$smarty.get.m}&o={$smarty.get.o}&validate=anular"><span><span> <img src="{$action.icono}" /> {$lang.ir_a} {$lang.anular} </span></span></button>
		{/if}
		{assign var=actions value=$user->getAvailableOptionsForModule($documento, "anexar")}
		{if $action=reset($actions)}
			<button class="btn" href="anexar.php?m={$smarty.get.m}&poid={$smarty.get.poid}&o={$smarty.get.o}&oid={$smarty.get.oid}"><span><span> <img src="{$action.icono}" /> {$lang.ir_a} {$lang.anexar} </span></span></button> 
		{/if}
		{assign var=actions value=$user->getAvailableOptionsForModule($documento, "validar")}
		{if $action=reset($actions)}
			<button class="btn" type="submit"><span><span> <img src="{$action.icono}" /> {$lang.validar} </span></span></button>
		{/if}
		{assign var=actions value=$user->getAvailableOptionsForModule($documento, "revisar")}
		{if $action=reset($actions)}
			<button class="btn box-it" href="revisar.php?poid={$smarty.get.poid}&m={$smarty.get.m}&o={$smarty.get.o}"><span><span> <img src="{$action.icono}" /> {$lang.ir_a} {$lang.revisar} </span></span></button>
		{/if}
	</div>
</form>
