{*
Descripcion
    Plantilla para su uso en modalbox, incluye referencias a error, succes e info

En uso actualmente
    - /agd/upload.php

Variables
    · $file - array( size, name, type ) - if isset = muestra link de descarga
    · $solicitantes - array( objectos ) - Mostrar la lista de destinatarios donde se anexara el documento
*}
{assign var=empresaUsuario value=$user->getCompany()}
{assign var=mustPayCompanies value=$empresaUsuario->pagoPorSubcontratacion()}
{assign var=needsPay value=$empresaUsuario->needsPay()}
{assign var=noPay value=$empresaUsuario->obtenerDato('pago_no_obligatorio')}
{assign var=isFree value=$empresaUsuario->isFree()}
{assign var=restriccion value=false}
{assign var=numRequest value=0}
{assign var=clientsWithRequest value=0}
{assign var=step value=1}



<div {if !$mobile_device}style="width: 720px;"{/if}>
    <div class="box-title" style="font-size: 16px;">
        {$lang.anexar} - <strong title="{$documento->getUserVisibleName()}">{$documento->getUserVisibleName()|truncate:"60"}</strong>
    </div>

    <div class="linetip">
        <img src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/information.png" />
        <a href="https://support.dokify.net/entries/23817488" target="_blank">
            {$lang.texto_ayuda_documentos}
        </a>
    </div>

    <form name="anexar-documento" action="{$smarty.server.PHP_SELF}?m={$elemento|get_class}&o={$elemento->getUID()}&poid={$documento->getUID()}" class="form-to-box" {if !isset($anexosrc)}enctype="multipart/form-data"{/if} id="anexar-documento" method="POST" data-loading-lock="{$lang.wait_request_processing}" {if $pass}data-pass="true"{/if}>
        {include file=$errorpath}
        {include file=$succespath}
        {include file=$infopath}

        {assign var=empresas value=$documento->obtenerEmpresasSolicitantes($elemento, $user, $reqType, $selectedRequest)}
        {assign var=totalSolicitudes value=$documento->obtenerSolicitudDocumentos($elemento, $user, false, $reqType)}

        {if $needsPay && $empresas|count > 1}
            {assign var=restriccion value=true}
        {/if}

        {if true === is_countable($empresas) && count($empresas)}
            {if !isset($anexosrc)}
                <div class="step">{$step}{assign var=step value=$step+1}</div>
                <div class="cbox-content">
                    <input type="hidden" name="MAX_FILE_SIZE" value="104857600" />
                    <table><tr>
                        <td style="width: 190px;">
                            <div class="filecontainer">
                                <button class="btn" style="white-space: nowrap" onclick="return false;"><span><span>{$lang.seleccionar_archivo}...</span></span></button>
                                <input type="file" accept="image/*;capture=camera" size="6" {if isset($file)}complete="true"{/if} name="archivo" id="anexar" target="#nombre-archivo-seleccionado" unselectable="on" />
                            </div>
                        </td>
                        <td id="uploadline" style="height: 2em;">
                            {if isset($file)}
                                <img src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/information.png" class="link" title="{$lang.informacion_upload}" />
                            {/if}

                            <strong id="nombre-archivo-seleccionado">
                                {if isset($file)}
                                    <a title="{$file.name} ({if print round($file.size/1024)}{/if}Kb)" href="getuploaded.php?action=dl" target="async-frame">{$file.name} <i>({$file.type})</i></a>
                                {else}
                                    {assign var=permission value=$user->getAvailableOptionsForModule($elemento->getType()."_documento", "reanexar")}

                                    {if $permission && isset($anexosReattachable)}
                                        {$lang.o}

                                        <select class="go" style="margin-left:2em; width:250px">
                                            <option>{$lang.seleccionar_enviado}</option>

                                            {foreach from=$anexosReattachable item=anexo}
                                                <option value="/agd/anexar.php?poid={$documento->getUID()}&oid={$anexo->getUID()}&m={$elemento->getModuleName()}&o={$elemento->getUID()}&action=re{if $selectedRequest}&req={$selectedRequest->getUID()}{/if}">{$anexo->getRequestString(true)}</option>
                                            {/foreach}
                                        </select>
                                    {else}
                                        {$lang.esperando_cargar_archivos}
                                    {/if}
                                {/if}
                            </strong>
                        </td>
                    </tr></table>
                </div>
                <hr />
            {/if}

            <div id="post-process">
                {if $items}
                    {include file=$tpldir|cat:'multiupload.tpl'}
                {/if}
            </div>

            <div class="step">{$step}{assign var=step value=$step+1}</div>
            <div class="cbox-content">
                <table><tr>
                    <td style="width: 190px">{$lang.seleccionar_fecha_documento}</td>
                    <td>
                        <div>
                            {if isset($fecha_reanexion)}
                                <input type="text" name="fecha" class="datepicker" size="12" onchange="return false;" value="{$fecha_reanexion.dd}/{$fecha_reanexion.mm}/{$fecha_reanexion.yyyy}" matche="^([0][1-9]|[12][0-9]|3[01])(/|-)(0[1-9]|1[012])\2(\d{4})$" disabled style="background-color:#EEE" />
                            {else}
                                <input type="text" name="fecha" class="datepicker mustfocus" size="12" onchange="return false;" value="{$fecha.dd}/{$fecha.mm}/{$fecha.yyyy}" matche="^([0][1-9]|[12][0-9]|3[01])(/|-)(0[1-9]|1[012])\2(\d{4})$"/>
                            {/if}
                            <img src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/information.png" style="vertical-align:middle" class="link" title="{$lang.informacion_fecha_documento}" />
                        </div>
                    </td>
                </tr></table>
            </div>
            <hr />


            <div class="step">{$step}{assign var=step value=$step+1}</div>
            <div class="cbox-content">
                <span>{$lang.indica_quien_ve_documento}</span>

                {if $selectedRequest && $totalSolicitudes|count > 1}
                    - <span class="red">{$lang.mostrando_solicitud_seleccionada}.</span> <a href="anexar.php?m={$elemento->getModuleName()}&poid={$documento->getUID()}&o={$elemento->getUID()}" class="box-it">{$lang.ver_todas}</a>
                {/if}


                <div class="solicitudes {if $restriccion}lock-inputs{/if}" style="margin-top:2em" data-blockname="fieldset">
                    {arrayPush value='all' key='validation_payment_method' result=filtrosPartner}
                    {foreach from=$empresas item=empresa}
                        {if isset($uidUserCompany)}
                            {arrayPush value=$uidUserCompany key='uid_empresa_referencia' result=filtro}
                            {arrayPush var=$filtro value=$empresa result=filtro}
                        {else}
                            {assign var=filtro value=$empresa}
                        {/if}

                        {assign var=solicitudes value=$documento->obtenerSolicitudDocumentos($elemento, $user, $filtro, $reqType, $selectedRequest)}
                        {assign var=criteria value=$documento->getCriteria($empresa)}

                        {if $criteria}
                            <div style="text-indent: 2em;">
                                <a href="../agd/criteria.php?poid={$documento->getUID()}&comefrom={$empresa->getUID()}" class="modalframe">
                                    {$lang.view_company_criteria|sprintf:$empresa->getUserVisibleName()}
                                </a>
                            </div>
                        {/if}

                        <fieldset>
                            <legend>{$empresa->getUserVisibleName()}</legend>
                            <div class="solicitud-empresa-{$empresa->getUID()}">
                            {if $needsPay && $mustPayCompanies && $mustPayCompanies->contains($empresa) && !$noPay}
                                <div class="padded" style="text-align:center; font-size: 14px">{$lang.expl_certificado_necesario}. <a href="/app/payment/license">{$lang.pincha_aqui}</a>
                                </div>
                            {else}
                                {if !$documento->isIta()}
                                    {assign var=partners value="empresaPartner::getEmpresasPartners"|call_user_func:$empresa:null:$filtrosPartner:true}
                                    {if $partners}
                                        <div class="padded" style="color:#00661F;padding-top:0px">
                                            {$lang.info_validation_price}&nbsp;
                                            {$lang.mas_info_link|sprintf:"https://support.dokify.net/entries/25037498"}
                                        </div>
                                    {/if}
                                {/if}
                                {assign var=clientsWithRequest value=$clientsWithRequest+1}
                                {foreach from=$solicitudes item=solicitud}
                                    {assign var=atributo value=$solicitud->obtenerDocumentoAtributo()}
                                    {assign var="refSubcontratacion" value=$atributo->hasChainReference()}
                                    {assign var="refContracts" value=$atributo->hasContractsReference()}
                                    {assign var="replica" value=$atributo->getReplicaParent()}
                                    {assign var="referencia" value=$solicitud->obtenerAgrupadorReferencia()}
                                    {assign var=numRequest value=$numRequest+1}
                                    {assign var=doc value=$solicitud->obtenerDocumento()}
                                    {assign var="anexo" value=$solicitud->getAnexo()}
                                    {assign var="namelength" value=84}
                                    {assign var="actualizarFecha" value=false}
                                    {assign var="usingToAttach" value=false}
                                    {assign var="standAlone" value=false}

                                    {if $replica || $refSubcontratacion || $refContracts || $referencia}
                                        {assign var="standAlone" value=true}
                                    {/if}


                                    {if isset($anexosrc) && $anexosrc->compareTo($anexo)}
                                        {assign var="usingToAttach" value=true}
                                    {/if}



                                    {assign var=atributo value=$solicitud->obtenerDocumentoAtributo()}

                                    {assign var="ejemplo" value=$atributo->obtenerDocumentoEjemplo()}
                                    {if isset($ejemplo) && $ejemplo instanceof documento_atributo}
                                        {if $ejemplo->obtenerDato("descargar") && $ejemplo->isLoaded()}
                                            {if $ejemplo instanceof documento_atributo}
                                                {assign var="namelength" value=$namelength-30}
                                                {assign var="atributoEjemplo" value=$ejemplo}
                                            {/if}
                                        {/if}
                                    {/if}

                                    {assign var="permitidos" value=$atributo->obtenerFormatosAsignados()}

                                    <table style="table-layout:auto;">
                                        {assign var="requestStatus" value=$solicitud->getStatus()}
                                        <tr>
                                            <td>
                                                <div {if !$touch_device}style="white-space:nowrap"{/if}>

                                                    {if $standAlone}
                                                        <img title="{$lang.lock_solicitud}" src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/information.png" class="help" style="vertical-align:middle" />
                                                    {else}
                                                        <img src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/arrow_up.png" style="vertical-align:middle" />
                                                    {/if}

                                                    {if $usingToAttach}
                                                        <img src="{$resources}/img/famfam/arrow_right.png" alt="->" style="vertical-align:middle;margin:3px 2px 3px 3px;" />
                                                    {else}

                                                        <input type="checkbox"  name="selected[]" value="{$solicitud->getUID()}"
                                                            {if !$standAlone && !$restriccion && ($requestStatus != 2 && $requestStatus != 1) || $applyForAll }
                                                                checked
                                                            {/if}

                                                            {if $applyForAll || (isset($anexosrc) && $standAlone)}
                                                                disabled
                                                            {/if}


                                                            {if $requestStatus == 1}
                                                                {assign var="classStatus" value=confirm}
                                                                data-confirm-once="{$lang.confirm_reanexar}"
                                                            {elseif $requestStatus == 2}
                                                                {assign var="classStatus" value=confirm}
                                                                data-confirm-once="{$lang.confirm_renovar}"
                                                            {else}
                                                                {assign var="classStatus" value=""}
                                                            {/if}

                                                            {if $standAlone && !(isset($anexosrc))}
                                                                {assign var="classRestriction" value="lock-inputs-like-radio"}
                                                                {if $restriccion}
                                                                    data-ctx=".solicitud-empresa-{$empresa->getUID()}"
                                                                {else}
                                                                    data-ctx=".solicitudes"
                                                                {/if}
                                                            {else}
                                                                {assign var="classRestriction" value="disable-button-anychecked"}
                                                                target=".lock-inputs-like-radio"
                                                            {/if}

                                                            class="attr-upload solicitante {$classStatus} {$classRestriction}"
                                                            data-filter=".solicitante"
                                                        />
                                                    {/if}

                                                    <div class="line-block">{$solicitud->getHTMLStatus()}</div>

                                                    <span class="help" title="{$solicitud->getUserVisibleName()|strip_tags}">{$solicitud->getUserVisibleName(true)|truncate:$namelength}</span>
                                                </div>
                                            </td>
                                            <td class="padded">

                                            {if !$touch_device}
                                                {if isset($atributoEjemplo)}
                                                    {assign var="infoEjemplo" value=$atributoEjemplo->getFileInfo($elemento, true)}

                                                    {if $infoEjemplo}
                                                        <button class="btn link" style="float:right" target="#async-frame" href="../agd/descargar.php?poid={$doc->getUID()}&o={$elemento->getUID()}&oid={$infoEjemplo.uid_anexo}&m={$elemento->getType()}&descargable=true&action=dl&context=attach&comefrom={$solicitud->getUID()}{if $selectedRequest}&req={$selectedRequest->getUID()}{/if}"><span><span style="white-space:nowrap;"><img src="{$resources}/img/famfam/disk.png" />&nbsp; {$lang.descargar_modelo}</span></span></button>
                                                    {/if}
                                                {/if}
                                            {/if}

                                            {if true === is_countable($permitidos) && count($permitidos)}
                                                <span class="formato-solicitante" rel="#solicitud-{$solicitud->getUID()}">
                                                    {foreach from=$permitidos item=permitido}
                                                        <img title="{$permitido->getAssignName(false)}" src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/{$permitido->getIcon()}" rel="{$permitido->getUserVisiblename()}" alt="{$permitido->getUserVisiblename()}" height="16" width="16" />
                                                    {/foreach}
                                                </span>
                                            {/if}

                                            </td>
                                        </tr>


                                    {if $agrupamientoAuto = $atributo->getAgrupamientoAuto()}
                                        {assign var=arrayAgrupadores value=$agrupamientoAuto->obtenerAgrupadores()}
                                        <tr>
                                            <td style="padding: 5px 0 0 1%; vertical-align: top;" colspan="2">
                                                <hr style="margin-bottom: 0.5em" />
                                                <div>
                                                    {assign var=listType value='<strong>'|cat:$agrupamientoAuto->getUserVisibleName()|cat:'</strong>'}
                                                    {$lang.selecciona_ajusten_situacion|sprintf:$listType}

                                                    {if true === is_countable($arrayAgrupadores) && count($arrayAgrupadores)>1}
                                                        {$lang.o_marcar_si_no_en_lista|lower}
                                                        <input type="checkbox" data-src="#lista-agrupadores-{$atributo->getUID()}" data-alert="{$lang.comentario_obligatorio_respecto}" class="alternative" data-alternative="#anexo-comentario" name="not-in-list[{$atributo->getUID()}]" style="margin-top:0px"/>
                                                    {/if}

                                                    <select id="lista-agrupadores-{$atributo->getUID()}" name="lista-agrupadores[{$atributo->getUID()}][]" rel="blank" style="width: 550px" {if true === is_countable($arrayAgrupadores) && count($arrayAgrupadores)>2}multiple{/if}>
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

                                    {if $atributo->caducidadManual() && !$usingToAttach}
                                        {if is_traversable($duraciones)}
                                            {assign var="duraciones" value=$duraciones[0]}
                                        {/if}
                                        <tr>
                                            <td class="padded" colspan="2" style="padding-left: 42px;">
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
                                    {elseif $duraciones && is_array($duraciones) && true === is_countable($duraciones) && count($duraciones) > 1 && !$usingToAttach}
                                        <tr>
                                            <td style="padding: 10px 0 0 44px"> Selecciona la duración del documento </td></tr>
                                            <tr>
                                            <td style="padding: 10px 0 5px 44px">
                                                {foreach from=$duraciones item=dias key=order}
                                                    <span style="white-space: nowrap;">
                                                    <input type="radio" name="caducidad[{$solicitud->getUID()}]" value="{$dias}" {if !$order}checked{/if} style="margin:0" />
                                                    {if $dias}
                                                        {$dias} {if is_numeric($dias)}{$lang.dias}{/if}
                                                    {else}
                                                        {$lang.no_caduca}
                                                    {/if}
                                                    </span>

                                                    {if ($order+1)!=count($duraciones)}|{/if}
                                                {/foreach}
                                            </td>
                                        </tr>
                                        {assign var="puthr" value=true}
                                    {/if}


                                    {if $puthr}
                                        <tr><td colspan="2"><hr style="margin: 5px 0" /></td></tr>
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


            {if !isset($fecha_reanexion) && $empresas->match($startList) && $user->accesoAccionConcreta($documento, 'validar')}
                <hr />
                <div class="step">{$step}{assign var=step value=$step+1}</div>
                <div class="cbox-content">
                    <span>
                        {$lang.validar_mis_documentos_automaticamente}
                        <input type="checkbox" name="autovalidate" />
                    </span>
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
            {if !$touch_device}
                <div style="float:left">
                    {if true === is_countable($empresas) && count($empresas)}
                        <button class="btn toggle" target="#comentar-documento"><span><span> <img src="{$resources}/img/famfam/user_comment.png" /> {$lang.comentario}</span></span></button>
                    {/if}
                </div>
            {/if}

            {if $numRequest>0}
                {if $action = @$user->getAvailableOptionsForModule($documento, "anexar")[0] || $reqType === null}
                    <button class="btn send" data-must=".solicitante" data-alert="{$lang.error_seleccionar_destinatarios}"><span><span> <img src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/attach.png" /> {$lang.anexar} </span></span></button>
                {/if}
            {/if}
            <div style="clear:both"></div>
        </div>


        {if isset($smarty.request.solicitante) }<input type="hidden" name="solicitante" value="{$smarty.request.solicitante}">{/if}
        {if isset($smarty.request.oid)}<input type="hidden" name="oid" value="{$smarty.request.oid}">{/if}
        {if isset($smarty.request.action)}<input type="hidden" name="action" value="{$smarty.request.action}">{/if}
        {if isset($smarty.request.referencia)}<input type="hidden" name="referencia" value="{$smarty.request.referencia}">{/if}
        {if isset($smarty.request.comefrom)}<input type="hidden" name="comefrom" value="{$smarty.request.comefrom}">{/if}
        {if isset($smarty.request.req)}<input type="hidden" name="req" value="{$smarty.request.req}">{/if}
        <input type="hidden" name="poid" value="{$smarty.request.poid}" />
        <input type="hidden" name="send" value="1" />
        {if isset($smarty.request.frameopen)}<input type="hidden" id="frameopen" value="{$smarty.request.frameopen}" />{/if}
    </form>
</div>
