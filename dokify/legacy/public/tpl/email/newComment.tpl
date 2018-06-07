{if isset($usuario)}
	<div style="text-align:center;color:#a2a2a2;font-size:12px">
		### {$lang.please_reply_above} ###
	</div>
{/if}
<div style="padding:20px 0;font-size:13px;font-family:Helvetica, arial, sans-serif">
	<div style="float:right;margin-left:3em;">
		<img src="{$smarty.const.RESOURCES_DOMAIN}/img/dokify-google-logo.png" alt="logo-dokify" width="143" height="59" />
	</div>

	{if $showNotice}
		<div style="color: #DF972D">
			<img style="vertical-align: middle;" src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/new.png" alt="new" />
			{$lang.new_reply_email}
		</div>

		<br />
	{/if}

	<div>
		{assign var="no_action" value='comment::NO_ACTION'|constant}
		{assign var="action_anexar" value='comment::ACTION_ATTACH'|constant}
		{assign var="action_validar" value='comment::ACTION_VALIDATE'|constant}
		{assign var="action_anular" value='comment::ACTION_REJECT'|constant}
		{assign var="action_email" value='comment::ACTION_EMAIL'|constant}
		{assign var="item" value=$element->getType()}
		{assign var="itemName" value=$lang.$item|lower}


		{$lang.email_greeting}<b>{if isset($usuario)} {$usuario->getName()}{elseif $nombreContacto} {$nombreContacto}{/if}</b>,

		<br /><br />

		{assign var=documentName value=$document->getUserVisibleName(false, $locale)}

		{if $element instanceof empresa || $element instanceof maquina}
			{assign var="preposition" value=$lang.pronoun_female}
		{else}
			{assign var="preposition" value=$lang.pronoun_male}
		{/if}

		{if $action == $no_action || $action == $action_email}
			{assign var="shadow" value='#E1E1E1'}
			{assign var="border" value='#CCC'}
			{assign var="bgimage" value='linear-gradient(#F8F8F8, #E1E1E1)'}
			{assign var="bgcolor" value='#E1E1E1'}

			{$lang.email_comment_expl|sprintf:$documentName:$preposition:$itemName:$element->getUserVisibleName()}
		{elseif $action == $action_anexar}
			{assign var="shadow" value='#DDE8EB'}
			{assign var="border" value='#CCC'}
			{assign var="bgimage" value='linear-gradient(#F8FBFC, #DDE8EB)'}
			{assign var="bgcolor" value='#DDE8EB'}


			{$lang.email_attach_expl|sprintf:$documentName:$preposition:$itemName:$element->getUserVisibleName()}
		{elseif $action == $action_validar}

			{assign var="shadow" value='#CDEB8B'}
			{assign var="border" value='#B0E048'}
			{assign var="bgimage" value='linear-gradient(#D8F5CD, #CDEB8B)'}
			{assign var="bgcolor" value='#CDEB8B'}


			{$lang.email_validate_expl|sprintf:$documentName:$preposition:$itemName:$element->getUserVisibleName()}
		{elseif $action == $action_anular}

			{assign var="shadow" value='#F4CDCB'}
			{assign var="border" value='#EBABA8'}
			{assign var="bgimage" value='linear-gradient(#F4CDCB, #FEBBBB)'}
			{assign var="bgcolor" value='#F4CDCB'}

			{$lang.email_anulacion_explicacion|sprintf:$documentName:$preposition:$itemName:$element->getUserVisibleName()}
		{/if}
		<br /><br /><br />

		{if $comment}
			<div style="clear:both;">
				<img id="commentPhoto" class="photo-user" src="{$from->getImage(false)}" width="48" style="border-radius:3px;margin-top:-3px; float:left;" />
				<div style="border:1px solid {$border};box-shadow:0 0 0 3px {$shadow};margin-left:67px;position:relative;border-radius:1px;">
					<div style="background-color:{$bgcolor};background-image:{$bgimage};background-repeat:repeat-x;padding:10px">
						<div style="float:right">
							<span style="color:#444;font-size:11px;text-shadow:1px 1px 0 rgba(255, 255, 255, 0.698)">
								{'d-m-Y H:i'|date:$date}
							</span>
						</div>
						<i>
							{$from->getName()}
							{if $from->esValidador()} - {$lang.validator_dokify}{/if}
						</i>
					</div>
					{assign var=message value=$commentId->getStaticAlertMessage($locale)}
					{assign var=link value=$commentId->getCommentIdFixLink($locale)}
					{if $message || $link}
						{assign var=messageClass value=$commentId->getClassMessage()}
						{if ($messageClass === 'comment-alert-red')}
							<div style="padding:10px;background-color: #f4cdcb;border-width: 2px 0;border-style: dotted;border-color: #EBABA8;">
								{$message}
								{$link}
							</div>
						{elseif ($messageClass === 'comment-alert-green')}
							<div style="padding:10px;background-color: #CFF6CC;border-width: 2px 0;border-style: dotted;border-color: #AFEBA7;">
								{$message}
								{$link}
							</div>
						{/if}
					{/if}
					<div style="padding:10px">{$comment|nl2br}</div>
				</div>
			</div>
			{assign var="link" value=$smarty.const.CURRENT_DOMAIN|cat:"/agd/#documentocomentario.php?m="|cat:$module|cat:"&poid="|cat:$document->getUID()|cat:$o|cat:"&o="|cat:$element->getUID()}

			<br /><br />
		{else}
			{assign var="link" value=$smarty.const.CURRENT_DOMAIN|cat:"/agd/#documentos.php?m="|cat:$module|cat:"&poid="|cat:$element->getUID()}
		{/if}


		{if $action == $action_anular && isset($attachment)}
			{assign var="downloadLink" value=$smarty.const.CURRENT_DOMAIN|cat:"/download/document?m="|cat:$module|cat:"&poid="|cat:$document->getUID()|cat:"&o="|cat:$element->getUID()|cat:"&oid="|cat:$attachment->getUID()}
			{$lang.download_file|sprintf:$downloadLink}<br><br>
		{/if}


		{if isset($usuario)}
			{$lang.link_show_comment|sprintf:$link}

			{if $usuario->esValidador() && isset($fileIds)}
				<div style="border-top:1px solid #ccc;margin: 20px 0"></div>

				{if $fileIds|count == 1}
					{foreach from=$fileIds item=fileId}
						<a href="{$smarty.const.CURRENT_DOMAIN}/agd/#validation.php?fileId={$fileId->getUID()}">
							{$lang.related_fileids_comment}
						</a>
					{/foreach}
					<br />
				{else}
					{$lang.related_fileids_comment}:
					<br />
					<br/ >
					{foreach from=$fileIds item=fileId key=docIndex}
						<a href="{$smarty.const.CURRENT_DOMAIN}/agd/#validation.php?fileId={$fileId->getUID()}">{$lang.documento} {$docIndex+1}</a><br>
					{/foreach}
				{/if}
			{/if}
		{else}
			{assign var="link" value=$smarty.const.CURRENT_DOMAIN|cat:"/agd/#documentos.php?m="|cat:$module|cat:"&poid="|cat:$element->getUID()|cat:"&doc="|cat:$document->getUID()}
			{$lang.link_show_document|sprintf:$link}<br><br>
			{include file=$smarty.const.DIR_ROOT|cat:'/tpl/email/pie.tpl'}
		{/if}
	</div>
</div>
