{assign var=commentId value=$comment->getCommentId()}
{assign var=argument value=$commentId->getValidationArgument()}
{assign var=deleted value=$commentId->isDeleted()}
{assign var=isCron value=$commentId->isCron()}
{assign var=action value=$comment->getAction()}
{assign var=userComment value=$comment->getCommenter()}
{assign var=userReply value=$comment->getReplyUser()}
{assign var=commentText value=$comment->getComment()}
{assign var=documento value=$commentId->getDocument()}
{assign var=elemento value=$commentId->getElement()}
{assign var=affectedRequirements value=$commentId->affectTo($user, $req)}
{assign var=isEditable value=false}
{foreach from=$affectedRequirements item=solicitud}
    {if $solicitud->isEditableBy($user)}
        {assign var=isEditable value=true}
    {/if}
{/foreach}

{if $userComment}
    {assign var=userExists value=$userComment->exists()}
{else}
    {assign var=userExists value=false}
{/if}

{if $action == 'comment::ACTION_ATTACH'|constant}
    <input type="hidden" name="attach_comment_id" value="{$commentId->getUID()}" />
    {assign var=class value="attached"}
    {assign var=img value=$smarty.const.RESOURCES_DOMAIN|cat:"/img/famfam/attach.png"}
    {if $isCron}
        {assign var=text value=$lang.change_attached_document}
    {else}
        {assign var=text value=$lang.attached_document}
    {/if}

{elseif $action == 'comment::ACTION_REJECT'|constant}

    {assign var=class value="cancel"}
    {assign var=img value=$smarty.const.RESOURCES_DOMAIN|cat:"/img/famfam/stop.png"}
    {assign var=text value=$lang.rejected_document}

{elseif $action == 'comment::ACTION_DELETE'|constant}

    {assign var=class value="deleted"}
    {assign var=img value=$smarty.const.RESOURCES_DOMAIN|cat:"/img/famfam/report_delete.png"}
    {assign var=text value=$lang.deleted_document}

{elseif $action == 'comment::ACTION_VALIDATE'|constant}

    {assign var=class value="validated"}
    {assign var=img value=$smarty.const.RESOURCES_DOMAIN|cat:"/img/famfam/spellcheck.png"}
    {assign var=text value=$lang.validated_document}

{elseif $action == 'comment::ACTION_EXPIRE'|constant}
    {assign var=class value="expired"}
    {assign var=img value=$smarty.const.RESOURCES_DOMAIN|cat:"/img/famfam/time.png"}
    {assign var=text value=$lang.change_expired_document}

{elseif $action == 'comment::ACTION_CHANGE_DATE'|constant}
    {assign var=class value="attached"}
    {assign var=img value=$smarty.const.RESOURCES_DOMAIN|cat:"/img/famfam/calendar.png"}
    {assign var=text value=$lang.change_date_document}

{elseif $action == 'comment::ACTION_SIGN'|constant}
    {assign var=class value="validated"}
    {assign var=img value=$smarty.const.RESOURCES_DOMAIN|cat:"/img/famfam/text_signature.png"}
    {assign var=text value=$lang.signed_document}
{else}

    {assign var=class value="commented"}
    {assign var=img value=$smarty.const.RESOURCES_DOMAIN|cat:"/img/famfam/comments.png"}
    {assign var=text value=$lang.commented_document}


    {if $userReply instanceof Iusuario}
        {assign var=text value=$lang.commented_document_reply|sprintf:$userReply->getName()}
    {/if}

    {if $action == 'comment::ACTION_EMAIL'|constant}
        {assign var=img value=$smarty.const.RESOURCES_DOMAIN|cat:"/img/famfam/email.png"}
    {/if}

{/if}
<div class="comment {$class}{if $deleted} transparent{/if}">
    <div class="photo-user">
        <img
            src="{if $userComment}{$userComment->getImage()}{else}{$smarty.const.RESOURCES_DOMAIN}/img/symbol-avatar.png{/if}"
            {if $deleted}
                width="32" height="32"
            {else}
                width="48" height="48"
            {/if}
            title="{if $userComment}{$userComment->getHumanName()}{else}dokify{/if}"
        />

        {if !$deleted && $userComment && $userComment->compareTo($user)}
            <div class="change-photo"><a href="https://support.dokify.net/entries/25136308" TARGET="_blank">{$lang.change}</a></div>
        {/if}
    </div>

    <div class="container-comment box-shadow {$class}">
        <div class="discussion-bubble {$class}"></div>
        <div class="comment-action border-box {$class}">

            {if !$deleted}
                <div class="actions">
                    <a class="date" title="{'d-m-Y H:i:s'|date:$comment->getDate($timezone)}">{$comment->getDate()|elapsed}</a>

                    {if $userComment && $userComment->compareTo($user)}

                        {if $commentText}
                            <a href="/agd/documentocomentario.php?edit={$commentId->getUID()}&m={$moduleName}" class="convert-editable" target=".user-comment-{$comment->getUID()}" rel="#editable-estatus-{$comment->getUID()}" data-minheight="30" data-maxheight="100"><img width="15px" height="15px" src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/pencil-plain.png" title="{$lang.desc_editar}"></a>
                            <span id="editable-estatus-{$comment->getUID()}" class="editable-status"></span>

                            {if ($action == 'comment::NO_ACTION'|constant || $action == 'comment::ACTION_EMAIL'|constant)}
                                <a class="confirm send-info" href="/agd/documentocomentario.php?delete={$commentId->getUID()}&m={$moduleName}" data-confirm="{$lang.confirm_delete_comment}"><img width="15px" height="15px" src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/cross-byn.png" title="{$lang.eliminar}"></a>
                            {/if}
                        {/if}
                    {/if}
                </div>
            {/if}

            <img width="15px" height="15px" src="{$img}" alt="" class="comment-image" />

            {if $deleted}
                {$lang.comentario_eliminado}
            {else}
                <strong>
                    {if $userComment}{$userComment->getName()}{else}dokify{/if}
                    {if $userExists && $userComment->esValidador()}
                    - {$lang.validator_dokify}{/if}
                </strong>
                <span>{$text}{if !$userComment} {$lang.automatically}{/if}.</span>
            {/if}
        </div>

        {if !$deleted}
            {assign var=message value=$commentId->getStaticAlertMessage()}
            {assign var=link value=$commentId->getCommentIdFixLink()}
            {if $message || $link}
                {assign var=messageClass value=$commentId->getClassMessage()}
                <div class="{$messageClass}">
                    {$message}

                    {if $isEditable}
                        {$link}
                    {/if}
                </div>
            {/if}

            {if $commentText}
                <div class="user-comment-{$comment->getUID()} user-comment comment-text">{$commentText|nl2br}</div>
            {/if}
        {/if}


        {assign var=reqCount value=$affectedRequirements|count}
        {assign var=reqsVarCount value=false}
        {if isset($reqs)}
            {assign var=reqsVarCount value=$reqs|count}
        {/if}
        {if $reqCount && !$deleted}
            <div class="footer-comment {if !$deleted && $commentText}hascomment{/if}">

                {assign var=check value=false}
                {if $reqsVarCount==$reqCount}
                    {assign var=forAllReq value=true}
                {else}
                    {assign var=forAllReq value=false}
                {/if}

                <div class="footer-left">
                    {assign var=dataTarget value="requiriments-"|cat:$comment->getUID()}
                    {include file=$tpldir|cat:'/comment/requestTitle.tpl'}
                </div>

                <div class="footer-right">
                    {if $userComment && !$user->compareTo($userComment)}
                        <button class="affect-to-requirement button xxs grey reply" title="{$lang.reply}" data-gravity="w" data-target="#all-requirements" data-selected="#requiriments-{$comment->getUID()}" data-counter="#apply-to-count" data-focus="#textarea-comment" data-from="{$userComment->getName()}" data-total="{$reqsVarCount}" data-totaltext="{$lang.to_all} {$lang.requirements}" data-id="{$commentId->getUID()}"
                        data-targetid="#replyId" data-selectorfrom="#recipient">
                            <img width="15px" height="15px" src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/reply-byn.png" style="vertical-align:middle">
                        </button>
                    {/if}
                </div>

                {if $reqCount}
                    <div id="requiriments-{$comment->getUID()}" class="requirement-comment requirement-list-comment">
                        {include file=$tpldir|cat:'/comment/requestList.tpl'}
                    </div>
                {/if}

            </div>
        {/if}
    </div>
</div>