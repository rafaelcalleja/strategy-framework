<div class="box-title">{$lang.ayuda}</div>
<form action="{$smarty.server.PHP_SELF}" method="POST" class="form-to-box">
	{include file=$errorpath}
	{include file=$succespath}
	{include file=$infopath}

	<div class="cbox-content" style="width: 550px">
		Ayuda por chat - Esta funcionalidad aun se encuentra en fase BETA y por eso solo esta disponible para algunos usuarios.
		<hr />
		<div class="stream" rel="helpdesk"></div>
	</div>
	<div class="cboxButtons">
		<button href="ayuda.php?mode=chat" class="btn"><span><span>Ayuda por chat</span></span></button>
	</div>
	<input type="hidden" name="send" value="1" />
</form>
