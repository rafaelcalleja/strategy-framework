<div class="cbox-content">
    <table style="width:100%; text-align: left;">
        <tr>
            <td style="width: 210px;" class="padded">
                    {$lang.seleccionar_fecha_documento}
            </td>
            <td colspan="3" class="padded">
                <input type="text" class="datepicker" name="date" size="10" matche="^([0][1-9]|[12][0-9]|3[01])(/|-)(0[1-9]|1[012])\2(\d{4})$" value="" />
                <img src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/information.png" style="vertical-align:middle" class="link" title="{$lang.informacion_fecha_documento}" />
            </td>
        </tr>
        {if true === $manualExpiring}
            <tr>
                <td colspan="4"><hr /></td>
            </tr>
            <tr>
                <td style="width: 210px;" class="padded">
                    Marca si este documento no caduca
                </td>
                <td style="min-width: 200px;"  class="padded">
                    <input type="checkbox" class="alternative" name="no-expiring" class="alternative" data-src="#expiration_date" data-src-value="no caduca"/>
                </td>
                <td style="width: 210px;" class="padded">
                    {$lang.seleccionar_fecha_caducidad}
                </td>
                <td class="padded">
                    {assign var="date" value=$duraciones|cat:' day'|strtotime|date_format:"%d/%m/%Y"}
                    <input id="expiration_date" type="text" name="expiration_date" class="datepicker" size="10" matche="^([0][1-9]|[12][0-9]|3[01])(/|-)(0[1-9]|1[012])\2(\d{4})$" value="" />
                    <img src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/information.png" style="vertical-align:middle" class="link" title="{$lang.text_info_expire_document}" />
                </td>
            </tr>
        {elseif true === is_array($duration) && count($duration) > 1}
            <tr>
                <td colspan="4"><hr /></td>
            </tr>
            <tr class="padded">
                <td style="width: 210px;" class="padded">
                    Selecciona la duración del documento
                </td>
                <td colspan="3" class="padded">
                    <ul>
                        {foreach from=$duration item=days key=order}
                            <li>
                                <input type="radio" name="duration" value="{$days}" style="margin:0" />
                                {if $days}
                                    {$days} {if is_numeric($days)}{$lang.days}{/if}
                                {/if}
                            </li>
                        {/foreach}
                    </ul>
                </td>
            </tr>
        {/if}
    </table>
</div>
