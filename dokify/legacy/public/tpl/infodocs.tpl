{assign var=empresaUsuario value=$user->getCompany()}

{if $selectedRequest}
    {assign var=solicitudes value=$documento->obtenerSolicitudDocumentos($elemento, $user, $cliente, null, $selectedRequest)}
    {assign var=total value=$documento->obtenerSolicitudDocumentos($elemento, $user, $cliente)|count}
{else}
    {assign var=solicitudes value=$documento->obtenerSolicitudDocumentos($elemento, $user, $cliente)}
    {assign var=total value=$solicitudes|count}
{/if}

<div class="box-title">
    <span title="{$documento->getUserVisibleName()}">{$documento->getUserVisibleName()|truncate:"62"}</span>
</div>
    <div style="width: 600px;">
        {if $documento->obtenerDato('description')}
            <p class="margenize padded">{$documento->obtenerDato('description')}</p>
        {/if}
        {foreach from=$solicitudes item=solicitud key=i}
            {assign var=estado value=$solicitud->getStatus()}
            {assign var=anexo value=$solicitud->getAnexo()}
            {assign var=atributo value=$solicitud->obtenerDocumentoAtributo()}
            {assign var=solicitante value=$atributo->getElement()}
            {assign var=empresa value=$atributo->getCompany()}
            {assign var=info value=$atributo->getInfo(false,"ficha")}
            {assign var=dateUpdated value=$solicitud->dateUpdated()}
            {assign var=isEditable value=$solicitud->isEditableBy($user)}

            {if $anexo}
                {assign var=previewFormat value=$anexo->canPreview()}
            {else}
                {assign var=previewFormat value=false}
            {/if}

            {assign var=name value=$info.alias}


            <div class="box-message-block {if $i+1 == count($solicitudes)}last-child{/if}">
                <table class="spaced">
                    <tr>
                        <td colspan="4" style="padding: 1em 1em 1em 3px">
                            <h2 style="line-height: 1.2em;">
                                {if $previewFormat}
                                    {assign var=url value="/agd/docview.php?poid="|cat:$anexo->getUID()|cat:"&m=anexo_"|cat:$elemento->getType()|cat:"&o="|cat:$elemento->getUID()}

                                    {assign var=url value=$url|urlencode}
                                    {assign var=url value="/app/viewer?format="|cat:$previewFormat|cat:"&title="|cat:$name|cat:"&file="|cat:$url}

                                    {if $smarty.request.url == $url}
                                        <img src="{$resources}/img/famfam/arrow_right.png" />
                                    {else}
                                        <a href="validar.php?poid={$documento->getUID()}&oid={$anexo->getUID()}&m={$elemento->getType()}&o={$elemento->getUID()}&url={$url|urlencode}" class="box-it" title="{$lang.previsualizar}" ><img src="{$resources}/img/famfam/zoom_in.png" /></a>
                                    {/if}
                                {/if}

                                {$name}

                                {if $empresaUsuario->compareTo($empresa)}
                                    {if $action=@$user->getAvailableOptionsForModule($atributo, 4, 1)[0]}
                                        <a href="{$action.href}?poid={$atributo->getUID()}" class="box-it" style="float: right"><img src="{$action.icono}" style="vertical-align:middle" /></a>
                                    {/if}
                                {/if}
                            </h2>
                        </td>
                    </tr>
                    <tr>
                        <td>{$lang.solicitante}:</td>
                        <td colspan="3">
                            {if $atributo->getOriginModuleName() != "empresa" }
                                {$empresa->getUserVisibleName()} &laquo;
                            {/if}

                            {$solicitud->getUserVisibleName(true)}
                        </td>
                    </tr>
                    <tr>
                        <td>{$lang.estado}:</td>
                        <td>
                            {$solicitud->getHTMLStatus()}

                            {if $anexo && $anexo->canApplyUrgent()}
                                {assign var=fileId value=$anexo->getFileId()}
                                {assign var=isRenovation value=$anexo->isRenovation()}

                                {if $fileId && $isEditable && ($estado == 1 || $isRenovation)}
                                    {if $anexo->isUrgent()}
                                        <img {if $isRenovation}style="vertical-align: text-top;"{else}style="vertical-align: middle;"{/if}src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/lightning_rojo.png" width="10px" height="10px" title="{$lang.validacion_urgente}" />
                                    {else}
                                        <button class="btn" href="applyUrgentValidation.php?fileId={$fileId->getUID()}" title="{$lang.request_urgent_validation}">
                                            <span class="strong"><span>
                                                <img style="vertical-align: middle;" src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/lightning_rojo.png" width="10px" height="10px" />
                                            </span></span>
                                        </button>
                                    {/if}
                                {/if}
                            {/if}
                        </td>

                        {if $anexo && $updateDate = $anexo->getUpdateDate($timezone)}
                            <td>
                                {$lang.ultimo_cambio}
                            </td>
                            <td>
                                <span class="light" style="color:#222">{$updateDate|date_format:"%d-%m-%Y %H:%M"}</span>

                                {*{if $user->esAdministrador()}<span class="light">{$anexo->getUID()}</span>{/if}*}

                                &nbsp;
                                <img src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/time_go.png" alt="l" style="vertical-align: middle;" width="12px" height="12px" />
                                <a href="#logui.php?m={$anexo->getModuleName()|strtolower}&poid={$anexo->getUID()}" class="unbox-it">
                                    {$lang.ver_log}
                                </a>
                            </td>

                        {else}
                            <td colspan="2"></td>
                        {/if}
                    </tr>
                    {assign var="referenced" value=$atributo->getReferenceType()}

                    {if $anexo && $fileinfo = $anexo->getInfo()}
                        {assign var=expiration value=$anexo->getExpirationTimestamp($timezone)}

                        <tr>
                            <td>{$lang.seleccionar_fecha_documento}:</td>
                            <td>
                                {'d-m-Y'|date:$anexo->getRealTimestamp($timezone)}

                                {if $isEditable}
                                    {if $dateUpdated}
                                        <img src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/calendar.png" style="vertical-align: middle; height:11px; margin-top:-1px" title="{$lang.fecha_ya_actualizada}" />
                                    {else}
                                        {if $op = @$user->getAvailableOptionsForModule($elemento->getType()."_documento", "Editar Fecha")[0]}
                                            <button class="btn box-it" href="anexar.php?poid={$documento->getUID()}&oid={$anexo->getUID()}&o={$elemento->getUID()}&m={$elemento->getType()}&action=date" title="{$lang.actualizar_fecha}"><span><span style="white-space:nowrap;"><img src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/date_edit.png" style="vertical-align: middle; height:11px; margin-top:-1px" /></span></span></button>
                                        {/if}
                                    {/if}
                                {/if}
                            </td>
                            <td>{$lang.fecha_anexion}:</td>
                            <td>{'d-m-Y'|date:$anexo->getTimestamp($timezone)}</td>
                        </tr>
                        <tr>
                            <td>{$lang.fecha_expiracion}:</td>
                            <td>
                                {if $info.duracion === 0 && !$atributo->caducidadManual()}
                                    {$lang.no_caduca}
                                {elseif $expiration}
                                    {'d-m-Y'|date:$expiration}

                                    {assign var=diff value=$expiration-$time}
                                    | {$diff/86400|ceil} {$lang.dias}
                                {else}
                                    {$lang.no_caduca}
                                {/if}
                            </td>
                            <td>{$lang.duracion}:</td>
                            <td>
                                {if $atributo->caducidadManual()}
                                    {$lang.manual}
                                {else}
                                    {if (is_numeric($info.duracion) && $info.duracion===0) || (is_numeric($expiration) && $expiration == 0)}
                                        {$lang.no_caduca}
                                    {else}
                                        {if $fileinfo.duration && !is_numeric($fileinfo.duration)}
                                            {$lang.hasta} {$fileinfo.duration}
                                        {else}
                                            {assign var=documentTime value=$anexo->getRealTimestamp($timezone)}
                                            {assign var=diffDuration value=$expiration-$documentTime}
                                            {$diffDuration/86400|ceil} ({$lang.dias})
                                        {/if}
                                    {/if}
                                {/if}
                            </td>
                        </tr>
                    {else}
                        <tr>
                            <td>{$lang.duracion}:</td>
                            <td>
                                {if $atributo->caducidadManual()}
                                    {$lang.manual}
                                {else}
                                    {if is_numeric($info.duracion) && $info.duracion==0 }
                                        {$lang.no_caduca}
                                    {else}
                                        {$info.duracion|replace:",":" - "} ({$lang.dias})
                                    {/if}
                                {/if}
                            </td>
                        </tr>
                    {/if}


                    {assign var="ejemplo" value=$atributo->obtenerDocumentoEjemplo()}
                    {if isset($ejemplo) && $ejemplo instanceof documento_atributo}
                        {if $ejemplo->obtenerDato("descargar") && $ejemplo->isLoaded()}
                            {if $ejemplo instanceof documento_atributo}
                                {assign var="namelength" value=$namelength-30}
                                {assign var="atributoEjemplo" value=$ejemplo}
                                {assign var="infoEjemplo" value=$atributoEjemplo->getFileInfo($elemento, true)}
                            {/if}
                        {/if}
                    {/if}





                    <tr>
                        <td>{$lang.carga}</td>
                        <td colspan="1">
                            {if ($info.obligatorio)}
                                {$lang.obligatorio}
                            {else}
                                {$lang.opcional}
                            {/if}
                        </td>

                        <td colspan="2">
                            {if $isEditable}
                                {if isset($infoEjemplo) && infoEjemplo}
                                    <button class="btn link" target="#async-frame" href="../agd/descargar.php?poid={$documento->getUID()}&o={$elemento->getUID()}&oid={$infoEjemplo.uid_anexo}&m={$elemento->getType()}&descargable=true&action=dl&context=info&comefrom={$solicitud->getUID()}{if $selectedRequest}&req={$selectedRequest->getUID()}{/if}" style="margin-left:-3px"><span><span style="white-space:nowrap;"><img src="{$resources}/img/famfam/disk.png" />&nbsp; {$lang.descargar_modelo}</span></span></button>
                                {/if}

                                <a class="btn unbox-it" href="#documentos.php?m={$elemento->getType()}&poid={$elemento->getUID()}&comefrom=descargables&rel={$solicitante}" style="margin-left:-3px"><span><span style="white-space:nowrap;"><img src="{$resources}/img/famfam/arrow_join_reverse.png" />&nbsp; {$lang.documentacion_asociada}</span></span></a>
                            {/if}
                        </td>
                    </tr>


                </table>
            </div>
        {/foreach}

        {if $selectedRequest && $total > 1}
            <div class="center" style="margin-top:1em">
                <span class="red">{$lang.mostrando_solicitud_seleccionada}.</span> <a href="informaciondocumento.php?m={$elemento->getModuleName()}&poid={$documento->getUID()}&o={$elemento->getUID()}" class="box-it">{$lang.ver_todas}</a>
            </div>
        {/if}
        {if isset($smarty.request.frameopen)}<input type="hidden" id="frameopen" value="{$smarty.request.frameopen}" />{/if}
    </div>
    <div class="cboxButtons">
            {assign var="options" value=$documento->getAvailableOptions($user, true, false, false)}
            {if $action= @$user->getAvailableOptionsForModule("documento", "enviar")[0]}
                <button class="btn box-it" href="{$action.href}?m={$smarty.get.m}&poid={$smarty.get.poid}&o={$smarty.get.o}&oid={$smarty.get.oid}"><span><span> <img src="{$action.icono}" /> {$lang.enviar} </span></span></button>
            {/if}
            {foreach from=$options item=option key=i }
                {if !in_array($option.uid_accion, array(57,41,10,37)) }
                    {if $option.href[0] == "#"}
                        {assign var="optionclass" value="unbox-it"}
                    {else}
                        {assign var="optionclass" value="box-it"}
                    {/if}
                    <a class="btn {$optionclass}" href="{$option.href}" style="margin-left:-5px;"><span><span>
                        <img style="vertical-align: middle;" src="{$option.img}" />
                        {$option.innerHTML}
                    </span></span></a>
                {/if}
            {/foreach}
            {assign var="estadoAnexado" value='documento::ESTADO_ANEXADO'|constant}
            {if $anexo}
                {assign var=fileId value=$anexo->getFileId()}
            {/if}

            {if $user->esValidador() && $estado == $estadoAnexado && $fileId}

                <button class="btn" href="#validation.php?fileId={$fileId->getUID()}"><span><span> <img src="{$action.icono}" /> {$lang.validar} </span></span></button>
            {/if}
    </div>

