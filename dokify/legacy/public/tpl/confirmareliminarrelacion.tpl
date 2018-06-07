<div class="box-title">
	{$lang.solicitud_eliminar_relacion}
</div>
<form name="elemento-form-papelera" action="{$smarty.server.PHP_SELF}" class="form-to-box" id="elemento-form-papelera">
	<div>
		<div style="text-align: center">
			{include file=$errorpath}
		</div>
		<div class="cbox-content" style="width: 500px;">
			{include file=$alertpath}
			{$mensaje}
			<hr />
			<button class="btn toggle" target="#comentar-documento"><span><span> <img src="{$resources}/img/famfam/user_comment.png" /> {$lang.comentario}</span></span></button>
			<div style="display:none" id="comentar-documento">
				<hr />
				<div class="cbox-content">
					{$lang.comentario}...
					<br />
					<textarea name='response_message' id="anexo-comentario"></textarea>
				</div>
			</div>
		</div>
	</div>
	<input type="hidden" name="request" value="{$smarty.get.request}" />
	<input type="hidden" name="poid" value="{$smarty.get.poid}" />
	<input type="hidden" name="m" value="{$smarty.get.m}" />
	<input type="hidden" name="send" value="1" />
	<div class="cboxButtons">
		<button class="btn detect-click" name="action" value="accept"><span><span><img src="{$resources}/img/famfam/accept.png"> {$lang.confirmar} </span></span></button> 
		<div style="float:left">
			<button class="btn detect-click" name="action" value="reject"><span><span><img src="{$resources}/img/famfam/cancel.png"> {$lang.rechazar} </span></span></button>
		</div>
	</div>
</form>