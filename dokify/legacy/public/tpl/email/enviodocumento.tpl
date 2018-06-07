<div style="padding:10px 20px 0 0">
	<img src="{$smarty.const.RESOURCES_DOMAIN}/img/dokify-google-logo.png" style="float: right" alt="logo-dokify" />
	<h1 style="margin-top:0"> {$lang.documento_enviado} </h1>
	<p>
		<br />
		{$lang.email_greeting},
		<br><br>
		{$usuario->getHumanName()} te ha enviado el documento <strong>{$documento->getUserVisibleName()}</strong> desde dokify

		<br />
		{if $comentario}
			<br />
			{$lang.comentariosEmpresa|sprintf:$usuario->getHumanName()}
			<br><br>
			{$comentario}
		{/if}

		{if !$adjunto}
			<br /><br />
			{$lang.ver_envio_documento|sprintf:$link}
		{else}
			<br /><br />
			{$lang.descarga_envio_documento|sprintf:$urlPublicFile}
		{/if}
			<br /><br />
			{$lang.caduca_envio_documento}
			
	</p>
	<p>
		<br>
		{$lang.email_pie_equipo}
		<br /><br />
		<a href="https://dokify.net/">{$lang.volver_inicio}</a>
	</p>

	{include file=$smarty.const.DIR_ROOT|cat:'/tpl/email/pie.tpl'}
</div>

