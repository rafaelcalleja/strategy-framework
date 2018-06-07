{if $affectedRequirements|count}
	{if $affectedRequirements|count > 1}
		<span class="right-space">
			{if $comment || $check}{$lang.comentario}{/if} {$lang.for|lower}
			{assign var="countSolicitudes" value=$affectedRequirements|count}
			<strong {if $check}id="apply-to-count"{/if} data-init="{$countSolicitudes}" data-inittext=" {$lang.to_all} {$lang.requirements}" data-elementonechecked="true" data-counter="{$countSolicitudes}"  data-addtext="{$lang.requirements}" data-name="selected">
				{if $forAllReq} {$lang.to_all} {else} {$countSolicitudes} {/if} {$lang.requirements}
			</strong>

			<a id="show-all-req" class="slideToggle" data-target="#{if $dataTarget}{$dataTarget}{else}all-requirements{/if}" data-uncompressedtext="{$lang.ver_todos}" data-compressedtext="{$lang.ocultar}">{$lang.ver_todos}</a>
		</span>
	{else}
		{assign var="solicitud" value=$affectedRequirements|reset}
		<span class="right-space">
			{assign var="reqname" value=$solicitud->getUserVisibleName(true)}
			{if $comment || $check}{$lang.comentario}{/if} {$lang.for|lower} <strong {if $reqname|strlen>100}title="<div class='white-space:nowrap'>{$reqname}</div>"{/if}>{$reqname|string_truncate:90}</strong>
		</span>
	{/if}
	{if $check}<input type="hidden" id="replyId" name="replyId" value=""/>{/if}
{/if}