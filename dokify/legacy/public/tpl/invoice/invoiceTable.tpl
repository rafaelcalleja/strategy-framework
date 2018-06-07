<table id="invoice-info" style="width:100%; border-collapse: expand;" >
    <thead>
        <tr>
            <th style="padding:0.5em 0.2em;">{$lang.descripcion}</th>
            <th style="padding:0.5em 0.2em;">{$lang.cantidad}</th>
            <th style="padding:0.5em 0.2em;">{$lang.descuento} (%)</th>
            <th style="padding:0.5em 0.2em;">{$lang.precio_final} (€)</th>
            <th style="padding:0.5em 0.2em;">{$lang.subtotal} (€)</th>
        </tr>
        <tr ><th colspan="5"><div style="border-bottom: 2px solid #000000;"></div></th></tr>
    </thead>
    <tbody >

        {foreach from=$items item=item }
        <tr>
            {assign var="description" value=$item.description}
            <td style="padding: 0.6em;text-align: left;vertical-align: middle;">{$lang.$description}{if isset($item.staticDescription)}{$item.staticDescription}{/if}</td>
            <td style="padding: 0.6em;text-align: center;vertical-align: middle;">{$item.quantity}</td>
            <td style="padding: 0.6em;text-align: center;vertical-align: middle;">{if $item.discount_table}{$item.discount_table}{else}-{/if}</td>
            <td style="padding: 0.6em;text-align: center;vertical-align: middle;">{$item.unit_price}</td>
            <td style="padding: 0.6em;text-align: center;vertical-align: middle;">{if $item.subtotal}{$item.subtotal}{else}{$item.unit_price}{/if}</td>
        </tr>
        {/foreach}

        {if 0 < $fee}
            <tr>
                <td style="padding: 0.6em;text-align: left;vertical-align: middle;">{$lang.gastos_gestion}</td>
                <td style="padding: 0.6em;text-align: center;vertical-align: middle;">1</td>
                <td style="padding: 0.6em;text-align: center;vertical-align: middle;">-</td>
                <td style="padding: 0.6em;text-align: center;vertical-align: middle;">{$fee}</td>
                <td style="padding: 0.6em;text-align: center;vertical-align: middle;">{$fee}</td>
            </tr>
        {/if}
    </tbody>
    <tfoot>
        <tr ><td colspan="5"><div style="border-top: 1px dotted #CCCCCC;"></div></td></tr>
        <tr>
            <td colspan="4" style="color:#1B244D;text-align:right"><p style="margin: 0.5em 1em;;text-align:right">{$lang.subtotal}:</p></td>
            <td colspan="1"  style="color:#1B244D;text-align:right"><p style="margin: 0.5em 1em;;text-align:center"><strong>{$subtotal} €</strong></p></td>
        </tr>
        {if isset($tax)}
        <tr>
            <td colspan="4" style="color:#1B244D;text-align:right"><p style="margin: 0.5em 1em;;text-align:right">{$lang.iva} (21.0 %):</p></td>
            <td colspan="1"  style="color:#1B244D;text-align:right"><p style="margin: 0.5em 1em;;text-align:center"><strong>{$tax} €</strong></p></td>
        </tr>
        {/if}
        <tr>
            <td colspan="4" style="color:#1B244D;text-align:right"><p style="margin: 0.5em 1em;;text-align:right">Total:</p></td>
            <td colspan="1"  style="color:#1B244D;text-align:right"><p style="margin: 0.5em 1em;;text-align:center"><strong>{$total} €</strong></p></td>
        </tr>
    </tfoot>
</table>