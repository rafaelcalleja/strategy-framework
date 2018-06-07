<div class="padded" style="text-align: center;">
    <strong style="text-decoration: underline;">{$lang.company_documents|sprintf:$uploaderCompany->getUserVisibleName()}</strong>
</div>

{if count($clients)}
    <div class="padded" style="width:100%;">
        {foreach from=$clients item=client key=i}
            <strong>{$client}</strong>

            {foreach from=$documents[$i] item=document key=j}
                <div style="padding-left:20px;">
                    <span class="stat stat_{$document.statusData.status}" title="{$document.statusData.title}">{$document.statusData.stringStatus}</span>
                    {assign var="audit" value='validation::TYPE_VALIDATION_AUDIT'|constant}
                    {if $document.fileId}
                    {assign var="url" value="validation/download.php?fileId="|cat:$document.fileId->getUID()|cat:"&module="|cat:$document.fileId->getModule()}
                        <a href="{$url}">{$document.name}</a>{if $tab == $audit} ({'d/m/Y'|date:$document.uploadDate}){/if}
                    {else}
                        {$document.name}{if $tab == $audit} ({'d/m/Y'|date:$document.uploadDate}){/if}
                    {/if}
                </div>
            {/foreach}
        {/foreach}
    </div>
{else}
    <div class="padded">
        <strong>{$lang.no_resultados}</strong>
    </div>
{/if}