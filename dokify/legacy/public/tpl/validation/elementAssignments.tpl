<div class="padded" style="text-align: center;">
    <strong style="text-decoration: underline;">{$lang.element_assignments|sprintf:$element->getUserVisibleName()}</strong>
</div>

{if count($clients)}
    <div class="padded" style="width:100%;">
        {foreach from=$clients item=client key=i}
            <strong>{$client}</strong>

            {foreach from=$organizations[$i] item=organization key=j}
                <div style="padding-left:20px;">
                    <em>{$organization}</em>

                    {foreach from=$groups[$j] item=group}
                        <div style="padding-left:20px;">
                            {if $group.bounce}
                                {$lang.assigned_by_bouncing|sprintf:$group.name:$group.bounce}
                            {else}
                                "{$group.name}"
                            {/if}
                        </div>
                    {/foreach}
                </div>
            {/foreach}
        {/foreach}
    </div>
{else}
    <div class="padded">
        <strong>{$lang.no_resultados}</strong>
    </div>
{/if}
