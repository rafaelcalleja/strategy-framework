{assign var=empresaUsuario value=$user->getCompany()}
{assign var=mustPayCompanies value=$empresaUsuario->pagoPorSubcontratacion()}
{assign var=needsPay value=$empresaUsuario->needsPay()}
{assign var=isFree value=$empresaUsuario->isFree()}
{assign var=restriccion value=false}
{assign var=numRequest value=0}
{assign var=clientsWithRequest value=0}
<div {if !$mobile_device}style="width: 700px;"{/if}>
    <div class="box-title" style="font-size: 16px;">
        {$lang.firmar} - <strong title="{$documento->getUserVisibleName()}">{$documento->getUserVisibleName()|truncate:"60"}</strong>
    </div>
    <form name="anexar-documento" action="{$smarty.server.PHP_SELF}" class="form-to-box" id="firmar-documento" method="POST" data-confirm-text="seguro_firmar">
        {include file=$errorpath}
        {include file=$succespath}
        {include file=$infopath}

        {assign var=empresas value=$documento->obtenerEmpresasSolicitantes($elemento, $user, 1, $selectedRequest)}
        {assign var=totalSolicitudes value=$documento->obtenerSolicitudDocumentos($elemento, $user, false, 1)}

        {if $needsPay && $empresas|count > 1}
            {assign var=restriccion value=true}
        {/if}

        {if count($empresas)}
            <div class="cbox-content">
                <table><tr>
                    <td style="width: 190px">{$lang.seleccionar_fecha_emision}</td>
                    <td>
                        <div>
                            {if isset($fecha_reanexion)}
                                <input type="text" name="fecha" class="datepicker" size="12" onchange="return false;" value="{$fecha_reanexion.dd}/{$fecha_reanexion.mm}/{$fecha_reanexion.yyyy}" matche="^([0][1-9]|[12][0-9]|3[01])(/|-)(0[1-9]|1[012])\2(\d{4})$"/>
                            {else}
                                <input type="text" name="fecha" class="datepicker mustfocus" size="12" onchange="return false;" value="{$fecha.dd}/{$fecha.mm}/{$fecha.yyyy}" matche="^([0][1-9]|[12][0-9]|3[01])(/|-)(0[1-9]|1[012])\2(\d{4})$"/>
                            {/if}
                            <img src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/information.png" style="vertical-align:middle" class="link" title="{$lang.informacion_fecha_emision}" />
                        </div>
                    </td>
                </tr></table>
            </div>
            <hr />


            <div class="cbox-content">

                <br />
                <div class="message error margenize">
                    {$lang.aviso_firma_documentos_usuario|sprintf:$user->getHumanName()}
                </div>

                {if $selectedRequest && $totalSolicitudes|count > 1}
                    - <span class="red">{$lang.mostrando_solicitud_seleccionada}.</span> <a href="firmar.php?m={$elemento->getModuleName()}&poid={$documento->getUID()}&o={$elemento->getUID()}" class="box-it">{$lang.ver_todas}</a>
                {/if}

                <span style="line-height:2.5em">{$lang.indica_quien_ve_documento}</span>
                <div class="{if $restriccion}lock-inputs{/if}" data-blockname="fieldset">
                    {foreach from=$empresas item=empresa}
                        {assign var=solicitudes value=$documento->obtenerSolicitudDocumentos($elemento, $user, $empresa, 1, $selectedRequest)}
                        <fieldset>
                            <legend>{$empresa->getUserVisibleName()}</legend>

                            {if $needsPay && $mustPayCompanies && $mustPayCompanies->contains($empresa) && !$noPay}
                                <div class="padded" style="text-align:center; font-size: 14px">{$lang.expl_certificado_necesario}. <a href="/app/payment/license">{$lang.pincha_aqui}</a>
                                </div>
                            {else}
                                {assign var=clientsWithRequest value=$clientsWithRequest+1}
                                {foreach from=$solicitudes item=solicitud}
                                    {assign var="anexo" value=$solicitud->getAnexo()}
                                    {assign var="namelength" value=95}
                                    {assign var="reanexarDocumento" value=false}
                                    {assign var="actualizarFecha" value=false}


                                    {assign var=atributo value=$solicitud->obtenerDocumentoAtributo()}
                                    {assign var=doc value=$solicitud->obtenerDocumento()}
                                    {assign var="hasExample" value=$atributo->hasExample()}
                                    {assign var="ejemplo" value=$atributo->obtenerDocumentoEjemplo()}


                                    <table style="table-layout:auto;">
                                        {if $hasExample}
                                            {assign var=numRequest value=$numRequest+1}
                                            <tr>
                                                <td>
                                                    <div {if !$mobile_device}style="white-space:nowrap"{/if}>
                                                        {if $totalSolicitudes|count > 1}
                                                            <img title="{$lang.lock_sign}" src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/information.png" class="help" style="vertical-align:middle" />
                                                        {/if}
                                                        <input type="checkbox" name="selected[]" value="{$solicitud->getUID()}" class="attr-upload solicitante lock-inputs-like-radio" data-filter=".solicitante"/>
                                                        <span title="{$solicitud->getUserVisibleName()|strip_tags}" >{$solicitud->getUserVisibleName(true)|truncate:$namelength}</span>
                                                    </div>
                                                </td>

                                                <td class="padded">
                                                    {if !$mobile_device}

                                                        {if isset($ejemplo)}
                                                            {assign var="infoEjemplo" value=$ejemplo->getFileInfo($elemento, true)}

                                                            {if $infoEjemplo}
                                                                <button class="btn link" target="#async-frame" href="../agd/descargar.php?poid={$doc->getUID()}&o={$elemento->getUID()}&oid={$infoEjemplo.uid_anexo}&m={$elemento->getType()}&descargable=true&action=dl&comefrom={$solicitud->getUID()}"><span><span style="white-space:nowrap;"><img src="{$resources}/img/famfam/disk.png" />&nbsp; {$lang.descargar_modelo}</span></span></button>
                                                            {/if}
                                                        {/if}

                                                    {/if}
                                                </td>
                                            </tr>


                                            {if $agrupamientoAuto = $atributo->getAgrupamientoAuto()}
                                                {assign var=arrayAgrupadores value=$agrupamientoAuto->obtenerAgrupadores()}
                                                <tr>
                                                    <td style="padding: 5px 0 0 1%; vertical-align: top;" colspan="2">
                                                        <hr />
                                                        <div>
                                                            {assign var=listType value='<strong>'|cat:$agrupamientoAuto->getUserVisibleName()|cat:'</strong>'}
                                                            {$lang.selecciona_ajusten_situacion|sprintf:$listType}

                                                            {if count($arrayAgrupadores)>1}
                                                                {$lang.o_marcar_si_no_en_lista|lower}
                                                                <input type="checkbox" data-src="#lista-agrupadores-{$atributo->getUID()}" data-alert="{$lang.comentario_obligatorio_respecto}" class="alternative" data-alternative="#anexo-comentario" name="not-in-list[{$atributo->getUID()}]" style="margin-top:0px"/>
                                                            {/if}

                                                            <select id="lista-agrupadores-{$atributo->getUID()}" name="lista-agrupadores[{$atributo->getUID()}][]" rel="blank" style="width: 550px" {if count($arrayAgrupadores)>2}multiple{/if}>
                                                                {foreach from=$arrayAgrupadores item=agrupador}
                                                                    <option value="{$agrupador->getUID()}"> {$agrupador->getUserVisibleName()} </option>
                                                                {/foreach}
                                                            </select>
                                                        </div>
                                                        <div style="padding-top: 6px;">
                                                            {$lang.usa_tecla_control}
                                                        </div>
                                                    </td>
                                                </tr>
                                            {/if}


                                            {assign var="puthr" value=false}
                                            {assign var="duraciones" value=$atributo->obtenerDuraciones()}

                                            {if $atributo->caducidadManual()}
                                                {if is_traversable($duraciones)}
                                                    {assign var="duraciones" value=$duraciones[0]}
                                                {/if}
                                                <tr>
                                                    <td class="padded" colspan="2" style="padding-top:0px; text-align: right;">
                                                        Marca si este documento no caduca <input type="checkbox" class="alternative"
                                                        data-src="#caducidad-manual-{$solicitud->getUID()}"
                                                        data-src-value="no caduca"
                                                        /> |

                                                        {$lang.seleccionar_fecha_caducidad}

                                                        {assign var="date" value=$duraciones|cat:' day'|strtotime|date_format:"%d/%m/%Y"}
                                                        <input type="text" name="caducidad[{$solicitud->getUID()}]" id="caducidad-manual-{$solicitud->getUID()}" class="datepicker" size="8" onchange="return false;" value="{$date}" matche="^([0][1-9]|[12][0-9]|3[01])(/|-)(0[1-9]|1[012])\2(\d{4})$" />
                                                        <img src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/information.png" class="link" title="{$lang.text_info_expire_document}" style="vertical-align:middle" />
                                                    </td>
                                                </tr>
                                                {assign var="puthr" value=true}
                                            {elseif $duraciones && is_array($duraciones) && count($duraciones) }
                                                <tr>
                                                    <td style="padding-left: 1%"> Selecciona la duración del documento </td></tr>
                                                    <tr>
                                                    <td style="padding-left: 1%">
                                                        {foreach from=$duraciones item=dias key=order}
                                                            <span style="white-space: nowrap;">
                                                            <input type="radio" name="caducidad[{$solicitud->getUID()}]" value="{$dias}" {if !$order}checked{/if} />
                                                            {if $dias} {$dias} {$lang.dias} {else} {$lang.no_caduca} {/if}</span>
                                                            {if ($order+1)!=count($duraciones)}|{/if}
                                                        {/foreach}
                                                    </td>
                                                </tr>
                                                {assign var="puthr" value=true}
                                            {/if}


                                            {if $puthr}
                                                <tr><td colspan="2"><hr /></td></tr>
                                            {/if}
                                        {else}
                                            {assign var="empresaAttr" value=$atributo->getCompany()}
                                            <tr>
                                                <td>
                                                    {$lang.plantilla_aun_no_disponible|replace:"%s":$empresaAttr->getUserVisibleName()}
                                                </td>
                                            </tr>
                                        {/if}
                                    </table>
                                {/foreach}
                            {/if}
                        </fieldset>
                    {/foreach}
                </div>
            </div>

            {if $clientsWithRequest > 1 && $restriccion}
                <div class="message error" style="border-top-width: 1px; margin-top:10px !important;">
                    {$lang.expl_carga_express}. <a href="/app/payment/license">{$lang.pincha_aqui}</a>
                </div>
            {/if}

            <div style="display:none" id="comentar-documento">
                <hr />
                <div class="cbox-content">
                    {$lang.comentario}...
                    <br />
                    <textarea name="comentario" id="anexo-comentario"></textarea>
                </div>
            </div>
        {else}
            <div style="padding:1em">
                {$lang.imposible_anexar_por_ahora}. <a href="#documentos.php?m={$elemento->getModuleName()}&poid={$elemento->getUID()}&comefrom=certificacion" class="unbox-it">{$lang.revisar_documento_certificacion_ahora}</a>
            </div>
        {/if}



        <div class="cboxButtons">
            {if !$mobile_device && $numRequest>0}
                <div style="float:left">
                    {if count($empresas)}
                        <button class="btn toggle" target="#comentar-documento"><span><span> <img src="{$resources}/img/famfam/user_comment.png" /> {$lang.comentario}</span></span></button>
                    {/if}
                </div>
            {/if}

            {if $canSign}
                {if $numRequest>0}
                    {if $action=reset($user->getAvailableOptionsForModule($documento, "firmar"))}
                        <button class="btn send"><span><span> <img src="{$action.icono}" /> {$lang.firmar}</span></span></button>
                    {/if}
                {/if}
            {else}
                <a href="usuario/modificar.php?poid={$user->getUID()}&amp;edit=id&amp;return={$smarty.server.REQUEST_URI|urlencode}" class="btn box-it"><span><span> <img src="{$resources}/img/famfam/pencil_add.png" />  {$lang.completa_campo_id_continuar}</span></span></a>
            {/if}
            <div style="clear:both"></div>
        </div>


        {if isset($smarty.request.solicitante) }<input type="hidden" name="solicitante" value="{$smarty.request.solicitante}">{/if}
        {if isset($smarty.request.oid)}<input type="hidden" name="oid" value="{$smarty.request.oid}">{/if}
        {if isset($smarty.request.action)}<input type="hidden" name="action" value="{$smarty.request.action}">{/if}
        {if isset($smarty.request.referencia)}<input type="hidden" name="referencia" value="{$smarty.request.referencia}">{/if}
        {if isset($smarty.request.o)}<input type="hidden" name="o" value="{$smarty.request.o}">{/if}
        <input type="hidden" name="poid" value="{$smarty.request.poid}" />
        <input type="hidden" name="m" value="{$smarty.request.m}" />
        <input type="hidden" name="send" value="1" />
    </form>
</div>