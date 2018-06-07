<div class="message highlight">
    <table>
        <tr>
            <td colspan="2">
                {$lang.confirma_datos_correctos}
                <br /><br />
            </td>
        </tr>
        <tr>
            <td>
                {$lang.tipo_documento_seleccionado}:
                <br /><br />
            </td>
            <td>
                <strong>{$nombredocumento}</strong>
            </td>
        </tr>
        <tr>
            <td>
                {$lang.alias_asignado}:
                <br /><br />
            </td>
            <td>
                <strong>{$smarty.request.nombre_documento}</strong>
            </td>
        </tr>
        <tr>
            <td>
                {$lang.selecciona_que_tipo_solicitara_documento}:
                <br /><br />
            </td>
            <td>
                <strong class="ucase">{$smarty.request.tipo_solicitante}</strong>
            </td>
        </tr>
        <tr>
            <td>
                {$lang.que_elemento_solicitara_el_documento}:
                <br /><br />
            </td>
            <td>
                <ul>
                    {foreach from=$elementosSeleccionados item=elemento}
                        <li><strong>{$elemento->getUserVisibleName()}</strong></li>
                    {/foreach}
                </ul>
                <br />
            </td>
        </tr>
        <tr>
            <td>
                {$lang.selecciona_a_que_elemento_se_solicitara_documento}:
                <br /><br />
            </td>
            <td>
                {if isset($smarty.request.tipo_receptores)}
                    {foreach from=$smarty.request.tipo_receptores item=receptor}
                        <strong class="ucase">{$lang.$receptor}</strong>
                        <br />
                    {/foreach}
                {/if}
            </td>
        </tr>
        <tr>
            <td>
                {$lang.duracion_en_dias}:
                <br /><br />
            </td>
            <td>
                {if $smarty.request.documento_duracion == "0"}
                    <strong>{$lang.no_caduca}</strong>
                {else}
                    <strong>{$smarty.request.documento_duracion} {$lang.dias}</strong>
                {/if}
            </td>
        </tr>
        {if $user->esStaff()}
        <tr>
            <td>
                {$lang.do_you_want_grace_period}:
                <br /><br />
            </td>
            <td>
                <strong>{$smarty.request.documento_grace_period} {$lang.dias}</strong>
            </td>
        </tr>
        {/if}
        <tr>
            <td>
                {$lang.modo_descarga}
                <br /><br />
            </td>
            <td>
                {if isset($smarty.request.documento_descarga)}
                    <strong>{$lang.si}</strong>
                {else}
                    <strong>{$lang.no}</strong>
                {/if}
            </td>
        </tr>
        <tr>
            <td>
                {$lang.solicitud_obligatoria}:
                <br /><br />
            </td>
            <td>
                {if isset($smarty.request.documento_obligatorio)}
                    <strong>{$lang.si}</strong>
                {else}
                    <strong>{$lang.no}</strong>
                {/if}
            </td>
        </tr>
        {if !isset($smarty.request.documento_descarga)}
            <tr>
                <td>
                    {$lang.referenciar_empresa}
                    <br /><br />
                </td>
                <td>
                    {if isset($smarty.request.referenciar_empresa)}
                        <strong>{$lang.si}</strong>
                    {else}
                        <strong>{$lang.no}</strong>
                    {/if}
                </td>
            </tr>
            <tr>
                <td>
                    {$lang.documento_ejemplo}
                    <br /><br />
                </td>
                <td>
                    {if isset($nombreEjemplo) && isset($smarty.request.doc_ejemplo)}
                        <strong>{$nombreEjemplo}</strong>
                    {else}
                        <strong>{$lang.no}</strong>
                    {/if}
                </td>
            </tr>
            <tr>
                <td>
                    {$lang.req_type}
                    <br /><br />
                </td>
                <td>
                    {if isset($smarty.request.req_type)}
                        <strong>{$lang.$nombreReqTipo}</strong>
                    {/if}
                </td>
            </tr>
        {else}
            <tr>
                <td>
                    {$lang.orientation}
                    <br /><br />
                </td>
                <td>
                    {if isset($smarty.request.orientation) && ($smarty.request.orientation == 'L')}
                        <strong>{$lang.apaisado}</strong>
                    {else}
                        <strong>{$lang.vertical}</strong>
                    {/if}
                </td>
            </tr>
        {/if}
    </table>
    <input type="hidden" name="end" value="1" />
</div>
<input type="hidden" name="step" value="4" />
