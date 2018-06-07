
<div class="box-title" style="{if $ie}width: 550px;{/if}">
	{$lang.resend_invitation_title}
</div>
	
	<form name="elemento-form-new" action="{$smarty.server.PHP_SELF}" class="form-to-box asistente" method="{$smarty.server.REQUEST_METHOD}" 
			id="elemento-form-new">
		<div>
			{include file=$succespath}
			{include file=$errorpath}
			{include file=$infopath}
			<div class="cbox-content" style="padding:10px">

				{if $activeInvitation}
					{$lang.resend_invitation_email|sprintf:$signInRequest->getInvitationEmail()}

					{if $alreadyInvited}
						<span class="red">{$lang.already_invitation_error}</span><br><br>
					{/if}	

					<input type="text" name="newemail" style="width: 100%;" value="{$signInRequest->getInvitationEmail()}"/><br />
					<input type="hidden" name="send" value="1" />
					<input type="hidden" name="action" value="enviar" />
					<input type="hidden" name="poid" value="{$signInRequest->getUID()}" id="poid" /><br /><br />
					
				{else}
					{$lang.error_invitation_invalid}
				{/if}
					
					
			</div>
		</div>
		<div class="cboxButtons">
			{if $activeInvitation}
				<button class="btn" type="submit"><span><span>{$lang.enviar}</span></span></button>
			{/if}

			{include file=$tpldir|cat:'button-list.inc.tpl'}
		</div>
	</form>	

	



	
	
