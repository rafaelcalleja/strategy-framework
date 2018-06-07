<div class="box-title">
	{$lang.comentario}
</div>

<form name="pre-comment" action="{$smarty.server.PHP_SELF}" class="form-to-box" id="pre-comment" method="POST">
	{include file=$errorpath}
	{include file=$succespath}
	{include file=$infopath}

	{include file=$smarty.const.DIR_TEMPLATES|cat:'resend.tpl'}

	<div class="cbox-content" style="width: 550px">
		{$lang.$text|default:$text}:
		<br />
		<textarea name="comment" style="width: 100%"></textarea>
	</div>

	<input type="hidden" name="send" value="1" />

	<div class="cboxButtons">
		<button class="btn" type="send"><span><span>
			<img src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/accept.png" /> {$lang.continuar}
		</span></span></button>
	</div>
</form>
