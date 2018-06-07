<form method="POST" action="{$smarty.server.PHP_SELF}">
    <div class="box-title">
        {$lang.clients_filter}
    </div>

    <div class="cbox-content">
        <div class="cbox-list-content item-list" style="width: 100%;">
            <table>
                {foreach from=$clients item=client}
                    <tr class="{if $client.selected}selected-row{/if}">
                        <td>
                            <span><input type="checkbox" name="clients[]" class="line-check" value="{$client.uid}" {if $client.selected}checked{/if}/> {$client.name}</span>
                        </td>
                    </tr>
                {/foreach}
            </table>
        </div>
    </div>

    <div class="cboxButtons">
        <button class="btn" type="submit"><span><span>{$lang.filter}</span></span></button>
    </div>
</form>