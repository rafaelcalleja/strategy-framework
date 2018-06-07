<div id="comments">
	<form action="{$smarty.server.PHP_SELF}" class="async-form" method="POST">
		<div id="comment-container">
			{if $req }
				<span class="red container-comment">{$lang.mostrando_solicitud_seleccionada}. {$lang.filter_requirement_expl_comment}.</span> <a href="/agd/#documentocomentario.php?m={$moduleName}&p=0&poid={$document->getUID()}&o={$element->getUID()}">{$lang.filter_requirement_expl_link_comment}</a>
			{/if}
			<div id="new-comment">
				<div class="photo-user">
					<img id="comment-photo" src="{$user->getImage()}" width="48"/>
					<div class="change-photo"><a href="https://support.dokify.net/entries/25136308--C%C3%B3mo-puedo-asociar-una-imagen-a-mi-usuario-" TARGET="_blank">Cambiar</a></div>
				</div>

				<div class="container-comment box-shadow commented">
					<div class=" discussion-bubble commented"></div>
					<div class="comment-action border-box commented">
						<img width="20px" height="20px" src="{$smarty.const.RESOURCES_DOMAIN}/img/48x48/iface/riesgos.png">
						<a href="{$smarty.const.CURRENT_DOMAIN}/agd/informaciondocumento.php?m={$moduleName}&poid={$document->getUID()}&o={$element->getUID()}&type=modal" class="box-it title">{$document->getUserVisibleName()}</a>

					</div>
					<div>
						<div id="new-comment-area">
							<div id="replyTo">{$lang.reply_to} <span id="recipient"></span></div>
							<textarea id="textarea-comment" placeholder="{$lang.leave_a_comment}" name="comentario" tabindex="1"></textarea>
						</div>
					</div>

					{assign var=check value=true}
					{assign var=forAllReq value=true}
					{assign var=affectedRequirements value=$reqs}

					<div id="selected-requirements" class="footer-comment">
						{include file=$tpldir|cat:'/comment/requestTitle.tpl'}
					</div>
					<div id="all-requirements" class="requirement-comment">
						{include file=$tpldir|cat:'/comment/requestList.tpl'}
					</div>

					{assign var="manually" value='watchComment::MANUALLY'|constant}
					{assign var="automatically_attachment" value='watchComment::AUTOMATICALLY_ATTACHMENT'|constant}
					{assign var="automatically_validation" value='watchComment::AUTOMATICALLY_VALIDATION'|constant}
					{assign var="automatically_comment" value='watchComment::AUTOMATICALLY_COMMENT'|constant}
					{assign var="automatically_change_date" value='watchComment::AUTOMATICALLY_CHANGE_DATE'|constant}

					{if $watchingComment}
						{assign var="assigned" value=$watchingComment->getAssigned()}

						{if  $assigned == $manually}
							{assign var="title" value=$lang.watching_thread_manually}
						{elseif $assigned == $automatically_attachment}
							{assign var="title" value=$lang.watching_thread_by_attachment}
						{elseif $assigned == $automatically_validation}
							{assign var="title" value=$lang.watching_thread_by_validation}
						{elseif $assigned == $automatically_comment}
							{assign var="title" value=$lang.watching_thread_by_comment}
						{elseif $assigned == $automatically_change_date}
							{assign var="title" value=$lang.watching_thread_by_change_date}
						{/if}

					{else}
						{assign var="title" value=$lang.receive_comments_email}
						
					 {/if}
				</div>

				 <div id="buttons-container">
						<div id="watch-comment">
							<button  type="submit" tabindex="3" href="/agd/documentocomentario.php?m={$moduleName}&p=0&poid={$document->getUID()}&o={$element->getUID()}&event={if $watchingComment}unwatch{else}watch{/if}" class="button post {if $watchingComment}black{else}grey{/if} m {if $watchingComment}actived{/if}" title="{$title}" data-text="{$lang.cargando}..." data-disable="true" data-gravity="w">
								{if $watchingComment} {$lang.stop_watching_thread} {else} {$lang.watch_thread} {/if}
							</button>
						</div>
						<div id="comment-button">
							<button tabindex="2" type="submit" class="button green m send" data-disable="true" data-must="#textarea-comment,.solicitudes" data-alert="{$lang.cannot_be_empty_comment}, {$lang.must_select_requirement}";>{$lang.comment}</button>
						</div>
					</div>
			</div>

			<div class="closed-banner"></div>
		
			{if $comments && count($comments)}
				<div>
					{foreach from=$comments item=comment key=i}
						{include file=$tpldir|cat:'/comment/comment.tpl'}
					{/foreach}
				</div>
			{/if}
			
			{if count($commentsRemaining)}
				<div id="all-comments">
					{include file=$tpldir|cat:'loadmore.tpl'}
				</div>
			{/if}
		</div>
		<div style="clear:both;">
			<input type="hidden" name="poid" value="{$smarty.get.poid}" />
			<input type="hidden" name="m" value="{$smarty.get.m}" />
			<input type="hidden" name="o" value="{$smarty.get.o}" />
			<input type="hidden" name="send" value="1" />
		</div>
	</form>
</div>

