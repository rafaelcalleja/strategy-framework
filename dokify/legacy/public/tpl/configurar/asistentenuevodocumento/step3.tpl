<div class="message highlight">
    <table>
        <tr>
            <td colspan=2 >
            {$lang.tipo_documento_seleccionado}: <strong>{$nombredocumento}</strong>
            <br /><br />
            </td>
        </tr>
        {if $parent instanceof agrupamiento}
        <tr>
            <td>
                {$lang.solicitar_desde_cualquier_agrupador}
            </td>
            <td style="text-align: right;">
                <input type="checkbox" name="agrupamiento" value="{$parent->getUID()}" class="toggle" target=".lista-solicitantes-agrupador,#documento-replica" {if $elemento instanceof agrupamiento}checked disabled{/if}/>
                <br /><br /><br />
            </td>
        </tr>
            {if $user->esStaff()}
            <tr id="documento-replica" style="{if !$elemento instanceof agrupamiento}display:none{/if}">
                <td>
                    {$lang.replicar_en_cada_agrupador}
                </td>
                <td style="text-align: right;">
                    <select name="replica" style="width:200px">
                        <option value="0">No</option>
                        <option value="1">Si</option>
                        <option value="2">Si, usar un tipo diferente para cada replica</option>
                    </select>
                    <br /><br /><br />
                </td>
            </tr>
            {/if}
        {/if}

        <tr>
            <td>
                {$lang.modo_descarga}
            </td>
            <td style="text-align: right;">
                <input type="checkbox" name="documento_descarga" class="toggle" target="#is-mandatory,#duration-days,#reference-company,#form-line-requirement-type,#example-document,#form-line-orientation-type" />
                <br /><br />
            </td>
        </tr>

        {if !isset($elemento)}
            <tr class="lista-solicitantes-agrupador">
                <td colspan="2">
                    {$lang.que_elemento_solicitara_el_documento}
                </td>
            </tr>
        {/if}
        <tr class="lista-solicitantes-agrupador">
            <td colspan="2">
                {if isset($elemento)}
                    <input type="hidden" name="id_solicitante[]" value="{$elemento->getUID()}" />
                {else}
                    <select name="id_solicitante[]" size="5" multiple>
                        {foreach from=$elementos item=elemento}
                            <option value="{$elemento->getUID()}">{$elemento->getUserVisibleName()}</option>
                        {/foreach}
                    </select>
                    <br /><br />
                {/if}
            </td>
        </tr>
        <tr id="is-mandatory">
            <td>
                {$lang.solicitud_obligatoria}
                <br /><br />
            </td>
            <td style="text-align: right;">
                <input type="checkbox" name="documento_obligatorio" checked />
                <br /><br />
            </td>
        </tr>
        <tr id="duration-days">
            <td>
                {$lang.duracion_en_dias}
                <br /><br />
            </td>
            <td style="text-align: right;">
                <input type="text" name="documento_duracion" style="width: 80px; text-align: right;" maxlength="25" value="0" />
                <br /><br />
            </td>
        </tr>
        {if $user->esStaff()}
            <tr id="grace-period">
                <td>
                    {$lang.do_you_want_grace_period}
                    <br /><br />
                </td>
                <td style="text-align: right;">
                    <input type="text" name="documento_grace_period" style="width: 80px; text-align: right;" maxlength="25" value="0" />
                    <br /><br />
                </td>
            </tr>
        {/if}
        {if isset($smarty.request.tipo_receptores)}
            {foreach from=$smarty.request.tipo_receptores item=receptor}
                {if ($receptor=='empresa') }
                    {assign var=hayReceptorEmpresa value=true}
                {/if}
            {/foreach}
        {/if}
        {if !isset($hayReceptorEmpresa) || count($smarty.request.tipo_receptores)>1}
            <tr id="reference-company" >
                <td>
                    {$lang.referenciar_empresa}
                    {if isset($hayReceptorEmpresa) }
                        <br />
                        * {$lang.referenciar_empresa_no_aplica}
                    {/if}
                    <br /><br />
                </td>
                <td style="text-align: right;">
                    <input type="checkbox" name="referenciar_empresa" />
                    <br /><br />
                </td>
            </tr>
        {/if}
        <tr id="example-document">
            <td>
                {$lang.documento_ejemplo}
            </td>
            <td data-affects="requirement-type" data-parts="!0">
                {if count($smarty.request.tipo_receptores)>1}
                    <strong>* {$lang.documento_ejemplo_no_posible}</strong>
                    <br /><br />
                {else}
                    <select name="doc_ejemplo">
                        <option value=0>{$lang.selecciona}</option>
                        {foreach from=$documentosEjemplo item=documentoEjemplo}
                            <option value="{$documentoEjemplo->getUID()}">{$documentoEjemplo->getUserVisibleName()}</option>
                        {/foreach}
                    </select>
                    <br /><br />
                {/if}
            </td>
        </tr>
        <tr id="form-line-requirement-type">
            <td>
                {$lang.req_type}
                <br /><br />
            </td>
            <td>
                <select name="req_type">
                    {foreach from=$tiposRequisitos item=tipoRequisito key=key}
                        <option value="{$key}">{$lang.$tipoRequisito}</option>
                    {/foreach}
                </select>
                <br /><br />
            </td>
        </tr>
        <tr id="form-line-orientation-type" style="display:none">
            <td>
                {$lang.orientation}
                <br /><br />
            </td>
            <td>
                <select name="orientation">
                        <option value="P">{$lang.vertical}</option>
                        <option value="L">{$lang.apaisado}</option>
                </select>
                <br /><br />
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <hr />
                <strong>{$lang.informacion_opcional}</strong>
            </td>
        </tr>
        <tr>
            <td>
                {$lang.codigo_documento}
            </td>
            <td style="text-align: right;">
                <input type="text" name="documento_codigo" style="width: 150px; text-align: right;" value="" />
                <br /><br />
            </td>
        </tr>
    </table>
    <input type="hidden" name="finish" value="1" />
</div>
<input type="hidden" name="step" value="3" />
