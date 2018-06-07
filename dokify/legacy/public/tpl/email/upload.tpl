<div style="padding:10px 20px 0 0">
		<img src="{$smarty.const.RESOURCES_DOMAIN}/img/dokify-google-logo.png" style="float: right" alt="logo-dokify" />
		<h1 style="margin-top:0"> <b>{$lang.upload_new_file}</b> </h1>
	<div style="clear: both">
		<br />
	</div>

	{$lang.email_greeting}{if $nombreContacto}<b> {$nombreContacto}</b>{/if},

	<br><br>

	{assign var="item" value=$element->getType()}
	{assign var="itemName" value=$lang.$item|lower}
	{assign var=documentName value=$document->getUserVisibleName(false, $locale)}
	
	{if $element instanceof empresa || $element instanceof maquina}
		{assign var="preposition" value=$lang.pronoun_female}
	{else}
		{assign var="preposition" value=$lang.pronoun_male}
	{/if}

	{$lang.upload_new_file_expl|sprintf:$documentName:$preposition:$itemName:$element->getUserVisibleName()}<br><br>
	
	{assign var="link" value=$smarty.const.CURRENT_DOMAIN|cat:"/agd/#documentos.php?m="|cat:$item|cat:"&poid="|cat:$element->getUID()|cat:"&doc="|cat:$document->getUID()}
	{$lang.link_show_document|sprintf:$link}

	<br><br>
	{$lang.email_pie_equipo}<br><br>
	{include file=$smarty.const.DIR_ROOT|cat:'/tpl/email/pie.tpl'}
</div>