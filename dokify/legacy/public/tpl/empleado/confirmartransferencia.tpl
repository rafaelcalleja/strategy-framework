<div class="box-title">
	{$lang.confirmar_transferencia}
</div>
<form name="confirmar-transferencia" action="{$smarty.server.PHP_SELF}" class="form-to-box" id="confirmar-transferencia">
	<div style="text-align: center">
		{include file=$errorpath}
		{include file=$succespath}
		{include file=$infopath}
	</div>
	<div class="cbox-content" style="width: 500px;">
		<p>
			{$empleado->getUserVisibleName()|string_format:$lang.confirmar_transferencia_texto}
			</br></br>
			{$lang.confirmar_transferencia_opciones}
		</p>

	</br>
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
	<div class="cboxButtons">
		<button class="btn detect-click" name="action" value="transfer"><span><span> <img src="{$resources}/img/famfam/arrow_right.png"> {$lang.transferir} </span></span></button>
		<button class="btn detect-click" name="action" value="share"><span><span> <img src="{$resources}/img/famfam/arrow_refresh.png"> {$lang.compartir} </span></span></button>
		<button class="btn detect-click" name="action" value="cancel"><span><span> <img src="{$resources}/img/famfam/delete.png"> {$lang.denegar} </span></span></button>
		<input type="hidden" name="send" value="1" />
		<input type="hidden" name="poid" id="poid" value="{$smarty.get.poid}" />
	</div>	
</form>