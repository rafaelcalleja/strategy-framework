<form method="POST" action="{$smarty.server.PHP_SELF}">
    <div class="box-title">
        {$lang.reqtypes_filter}
    </div>

    <div class="cbox-content">
        <div class="cbox-list-content item-list" style="width: 100%;">
            <table>
                {foreach from=$reqtypes item=reqtype}
                    <tr class="{if $reqtype.selected}selected-row{/if}">
                        <td>
                            <span><input type="checkbox" name="reqtypes[]" class="line-check" value="{$reqtype.uid}" {if $reqtype.selected}checked{/if}/> {$reqtype.name}</span>
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