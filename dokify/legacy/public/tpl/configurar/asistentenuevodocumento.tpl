{*{assign var="tpldir" value=$smarty.server.DOCUMENT_ROOT|cat:"/tpl/configurar/asistentenuevodocumento/" }*}
<div class="box-title">
    {$lang.asistente_nuevo_documento}
</div>
<form name="asistente-nuevo-documento" action="{$smarty.server.PHP_SELF}" method="post" class="form-to-box asistente" id="asistente-nuevo-documento" style="width: 600px;">
    <div style="text-align: center;">
        {include file=$errorpath}
        {include file=$succespath}
        {include file=$infopath}
        <div style="padding-top: 10px;">
            {if (isset($smarty.request.step)&&$smarty.request.step>0)}
                {foreach from=$documentos item=documento}
                    {if $documento.uid_documento == $smarty.request.tipo_documento }
                        {assign var="nombredocumento" value=$documento.nombre}
                    {/if}
                {/foreach}
            {/if}


            {* PRIMER PASO: SELECCIONAR EL TIPO DE DOCUMENTO *}
                {if (isset($smarty.request.step)&&$smarty.request.step==0)||!isset($smarty.request.step)}
                    {include file=$tpldir|cat:"configurar/asistentenuevodocumento/step1.tpl" }
                {/if}

            {* SEGUNDO PASO: SELECCIONAR TIPO DE ELEMENTO SOLICITANTE Y TIPO DE ELEMENTOS DE DESTINO *}
                {if (isset($smarty.request.step)&&$smarty.request.step==1)}
                    {if isset($smarty.request.tipo_documento)&&is_numeric($smarty.request.tipo_documento)&&$smarty.request.tipo_documento}
                        {include file=$tpldir|cat:"configurar/asistentenuevodocumento/step2.tpl" }
                    {else}
                        <div class="message error">
                            {$lang.comprueba_que_has_seleccionado_un_tipo_de_documento}
                        </div>
                        <br /><br />
                        {include file=$tpldir|cat:"configurar/asistentenuevodocumento/step1.tpl" }
                    {/if}
                {/if}

            {* TERCER PASO: SELECCIONAR DATOS DEL DOCUMENTO Y CONJUNTO DE ORIGENES *}
                {if (isset($smarty.request.step)&&$smarty.request.step==2)}
                    {if !isset($smarty.request.tipo_solicitante)||!$smarty.request.tipo_solicitante}
                        <div class="message error">
                            {$lang.selecciona_que_tipo_solicitara_documento}
                        </div>
                        <br /><br />
                        {include file=$tpldir|cat:"configurar/asistentenuevodocumento/step2.tpl" }
                    {else}
                        {if !isset($smarty.request.tipo_receptores)||!$smarty.request.tipo_receptores}
                            <div class="message error">
                                {$lang.selecciona_a_que_elemento_se_solicitara_documento}
                            </div>
                            <br /><br />
                            {include file=$tpldir|cat:"configurar/asistentenuevodocumento/step2.tpl" }
                        {else}
                            {include file=$tpldir|cat:"configurar/asistentenuevodocumento/step3.tpl" }
                        {/if}
                    {/if}
                {/if}

            {* CUARTO PASO: CONFIRMAR DATOS *}
                {if (isset($smarty.request.step)&&$smarty.request.step==3)}
                    {if isset($elementosSeleccionados)&&count($elementosSeleccionados)}
                        {include file=$tpldir|cat:"configurar/asistentenuevodocumento/step4.tpl" }
                    {else}
                        <div class="message error">
                                {$lang.selecciona_elemento_origen}
                        </div>
                        <br /><br />
                        {include file=$tpldir|cat:"configurar/asistentenuevodocumento/step3.tpl" }
                    {/if}
                {/if}


            <div class="cboxButtons">
                {if isset($smarty.request.step)}
                    <button type="submit" class="btn" onclick="this.form.step.value='{$smarty.request.step-1}';"><span><span> {$lang.atras} </span></span></button>
                {/if}
                <button class="btn" type="submit"><span><span> {$lang.continuar} </span></span></button>
            </div>
        </div>
    </div>



    {if (isset($smarty.request.step)&&$smarty.request.step>0)}
        {if isset($smarty.request.tipo_documento)&&is_numeric($smarty.request.tipo_documento) }
            <input type="hidden" name="tipo_documento" value="{$smarty.request.tipo_documento}" />
        {/if}
    {/if}


    {if (isset($smarty.request.step)&&$smarty.request.step>1)}
        {if isset($smarty.request.nombre_documento)}
            <input type="hidden" name="nombre_documento" value="{$smarty.request.nombre_documento}" />
        {/if}
        {if isset($smarty.request.tipo_solicitante)&&!empty($smarty.request.tipo_solicitante)}
            <input type="hidden" name="tipo_solicitante" value="{$smarty.request.tipo_solicitante}" />
        {/if}

        {if isset($smarty.request.tipo_receptores)}
            {foreach from=$smarty.request.tipo_receptores item=receptor}
                <input type="hidden" name="tipo_receptores[]" value="{$receptor}" />
            {/foreach}
        {/if}
    {/if}


    {if (isset($smarty.request.step)&&$smarty.request.step>2)}
        {if isset($smarty.request.documento_obligatorio)}
            <input type="hidden" name="documento_obligatorio" value="{$smarty.request.documento_obligatorio}" />
        {/if}

        {if isset($smarty.request.referenciar_empresa)}
            <input type="hidden" name="referenciar_empresa" value="{$smarty.request.referenciar_empresa}" />
        {/if}

        {if isset($smarty.request.req_type)}
            <input type="hidden" name="req_type" value="{$smarty.request.req_type}" />
        {/if}

        {if isset($smarty.request.doc_ejemplo)}
            <input type="hidden" name="doc_ejemplo" value="{$smarty.request.doc_ejemplo}" />
        {/if}

        {if isset($smarty.request.orientation)}
            <input type="hidden" name="orientation" value="{$smarty.request.orientation}" />
        {/if}

        {if isset($smarty.request.documento_descarga)}
            <input type="hidden" name="documento_descarga" value="{$smarty.request.documento_descarga}" />
        {/if}

        {if isset($smarty.request.documento_duracion)}
            <input type="hidden" name="documento_duracion" value="{$smarty.request.documento_duracion}" />
        {/if}

        {if isset($smarty.request.documento_grace_period)}
            <input type="hidden" name="documento_grace_period" value="{$smarty.request.documento_grace_period}" />
        {/if}

        {if isset($smarty.request.documento_codigo)}
            <input type="hidden" name="documento_codigo" value="{$smarty.request.documento_codigo}" />
        {/if}

        {if isset($smarty.request.agrupamiento)}
            <input type="hidden" name="agrupamiento" value="{$smarty.request.agrupamiento}" />
        {/if}

        {if isset($smarty.request.replica)}
            <input type="hidden" name="replica" value="{$smarty.request.replica}" />
        {/if}

        {if isset($smarty.request.id_solicitante)}
            {foreach from=$smarty.request.id_solicitante item=id}
                <input type="hidden" name="id_solicitante[]" value="{$id}" />
            {/foreach}
        {/if}
    {/if}


    {if isset($smarty.request.send)}<input type="hidden" name="send" value="1" />{/if}
    {if isset($smarty.request.m)}<input type="hidden" name="m" value="{$smarty.request.m}" />{/if}
    {if isset($smarty.request.poid)}<input type="hidden" name="poid" value="{$smarty.request.poid}" />{/if}
</form>
