<div style="padding:10px 20px 0 0">
		<img src="{$smarty.const.RESOURCES_DOMAIN}/img/dokify-google-logo.png" style="float: right" alt="logo-dokify" />
	<h1 style="margin-top:0"> <b>Documento procesado!</b> </h1>
	<div style="clear: both">
	</div>

		Listo! Hemos terminado de procesar tu documento. Este es el resumen

		{foreach from=$files item=file}
			<div> 
				El fichero <strong>{$file.name}</strong>

				{if $file.error}
					{assign var="error" value=$file.error}

					ha dado un error: <strong style="color:red">{$lang.$error|default:$error}</strong>
				{else}
					se ha <strong style="color:green">anexado {if $file.status == constant('documento::ESTADO_VALIDADO')}y validado{/if} en {$file.processed}</strong> items!
				{/if}
			</div>
		{/foreach}

		{if $errors}
			Si crees que todo está bien itentalo de nuevo pasados unos minutos o cargalo manualmente en <a href="{$smarty.const.CURRENT_DOMAIN}">dokify</a>
		{else}
			Para comprobar que todo está bien echa un vistazo en <a href="{$smarty.const.CURRENT_DOMAIN}">dokify</a>
		{/if}

		<br /><br />

		{$lang.email_pie_equipo}<br><br>
		{include file=$smarty.const.DIR_ROOT|cat:'/tpl/email/pie.tpl'}
</div>
