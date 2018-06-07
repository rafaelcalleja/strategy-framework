{if isset($fileId) && $fileId instanceof fileId}
    {if $anexo instanceof anexo}
        <form name="validar-documento" action="validar.php" id="validar-documento" class="async-form" method="POST" >
            <div class="validation-info-document">
                {assign var="document" value=$fileId->getDocument()}
                {assign var="element" value=$anexo->getElement()}
                {assign var="moduleName" value=$element->getModuleName()}
                {assign var="uploaderCompany" value=$anexo->getUploaderCompany()}


                <a href="{$smarty.const.CURRENT_DOMAIN}/agd/#validation.php?fileId={$fileId->getUID()}" title="Link permanente" class="title-doc">
                    {$document->getUserVisibleName()}
                </a>
                <br />
                <span class="info-doc">
                    {$lang.info_document_validate|sprintf:$element->getUserVisibleName():$element->getId():$lang.$moduleName}

                    {if $element instanceof maquina}
                        <br />
                        {$lang.matricula}: {$element->obtenerDato('matricula')|trim|default:"<span class='light'>No especificado</span>"}
                    {/if}
                </span>
            </div>

            <div class="validation-info-companies">
                {if $element instanceof empleado || $element instanceof maquina}
                    <table>
                        <tr>
                            <th></th>
                            <th align="left"><u>{$lang.empresas}:</u></th>
                        </tr>
                        {assign var="companies" value=$element->getCompanies()}
                        {foreach from=$companies item=elementCompany}
                            <tr>
                                <td style="vertical-align:middle">
                                    {if $uploaderCompany instanceof empresa && $uploaderCompany->compareTo($elementCompany)}<img src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/user_gray.png"  style="vertical-align:top;width:13px" alt="{$lang.company_uploader_user}" title="{$lang.company_uploader_user}" />{/if}
                                </td>
                                <td>
                                    {$elementCompany->getUserVisibleName()} ({$elementCompany->getCIF()})
                                </td>
                            </tr>
                        {/foreach}

                        {if $tab == $audit}
                            {assign var="trashCompanies" value=$element->getCompanies(true)}

                            {foreach from=$trashCompanies item=elementCompany}
                                {if $uploaderCompany instanceof empresa && $uploaderCompany->compareTo($elementCompany)}
                                    <tr>
                                        <td style="vertical-align:middle">
                                            <img src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/user_gray.png"  style="vertical-align:top;width:13px" alt="{$lang.company_uploader_user}" title="{$lang.company_uploader_user}" />
                                        </td>
                                        <td style="color: #ccc">
                                            {$elementCompany->getUserVisibleName()} ({$elementCompany->getCIF()})
                                        </td>
                                    </tr>
                                {/if}
                            {/foreach}
                        {/if}
                    </table>
                {/if}
            </div>

            <div class="clear"></div>

            <div class="validation-cont info">
                <table style="width:100%; text-align: left;">
                    <tr>
                        <td>
                            {$lang.seleccionar_fecha_documento} <strong>{$anexo->getRealTimestamp()|date_format:"%d/%m/%Y"}</strong>
                        </td>
                        <td>
                            {$lang.fecha_anexion} <strong>{$anexo->getTimestamp($timezone)|date_format:"%d/%m/%Y %H:%M"}</strong>
                        </td>

                        {if $tab == $review}
                            <td>
                                {assign var="validator" value=$fileId->getValidatior()}
                                {$lang.validado_por} <strong>{$validator->getUserVisibleName()}</strong>
                            </td>
                        {else}
                            <td>
                                {$lang.anexado} <strong>{$anexo->getTimestamp()|elapsed}</strong>
                            </td>
                        {/if}

                        <td>
                            <a class="slideToggle" data-target="#assignments">
                                <img src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/table_relationship.png" title="{$lang.element_assignments|sprintf:$element->getUserVisibleName()}" />
                            </a>
                        </td>

                        {if $uploaderCompany}
                            <td>
                                <a class="slideToggle" data-target="#documents">
                                    <img src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/folder.png" title="{$lang.company_documents|sprintf:$uploaderCompany->getUserVisibleName()}" />
                                </a>
                            </td>
                        {/if}
                    </tr>
                </table>

                <table style="width:100%; text-align: left;">
                    <tr>
                        <td id="assignments" class="async-info-load once" style="display:none" href="validation/elementAssignments.php?poid={$element->getUID()}&m={$fileId->getModule()}"></td>
                    </tr>
                    {if $uploaderCompany}
                        <tr>
                            <td id="documents" class="async-info-load once" style="display:none" href="validation/uploaderDocuments.php?poid={$uploaderCompany->getUID()}&tab={$tab}"></td>
                        </tr>
                    {/if}
                </table>
            </div>

            <div class="validation-cont">
                {assign var="url" value="validation/download.php?fileId="|cat:$fileId->getUID()|cat:"&module="|cat:$fileId->getModule()}
                {if $anexo->canPreview()}
                    <div class="element-cont">
                        {$lang.can_download|sprintf:$url}.
                    </div>
                    {assign var="moduleAttachment" value="anexo_"|cat:$fileId->getModule()}
                    <iframe id="framePreview" src="docview.php?poid={$anexo->getUID()}&amp;m={$moduleAttachment}&amp;o={$element->getUID()}&amp;dl=true"></iframe>
                {else}
                    <div class="element-cont">
                        {$lang.sin_previsualizacion} - <a href="{$url}"><b>{$lang.descargar}</b></a>.
                    </div>
                {/if}
            </div>

            <div id="comments" class="validation-cont">
                <div id="validation-cont-comment" >
                    {if count($comments)}
                        {foreach from=$comments item=comment key=i}
                            {include file=$tpldir|cat:'/comment/comment.tpl'}
                        {/foreach}
                    {/if}

                    {if count($commentsRemaining)}
                        <div id="previous-comments">
                            {include file=$tpldir|cat:'loadmore.tpl'}
                        </div>
                    {/if}
                </div>

                {if $tab != $review && !$fileId->fromHistory()}
                    <div class="validation-cont comment">
                        <img class="photo-user" src="{$user->getImage(false)}" width="48"/>
                        <div class="container-comment box-shadow">
                            <div class="discussion-bubble commented"></div>
                            <textarea id="commentValidator" name="comentario" placeholder="{$lang.anadir_comentario}"></textarea>
                        </div>
                    </div>
                {/if}
            </div>





            <div class="validation-cont">
                {foreach from=$anexos item=anexosCompany key=uidCompany}
                    {new result="companyAnexo" type="empresa" uid=$uidCompany}
                    <fieldset class="companyAnexo">
                        <legend>
                            {if isset($force)}<span style="display:none">{/if}
                                <input type="checkbox" class="line-check" checked="true" name="companies[]" value="{$companyAnexo->getUID()}"/>
                            {if isset($force)}</span>{/if}
                            {$companyAnexo->getUserVisibleName()}

                            {assign var=criteria value=$document->getCriteria($companyAnexo)}

                            {if $criteria}
                                <a class="box-it row-link" href="../agd/validation/criteria.php?poid={$document->getUID()}&comefrom={$companyAnexo->getUID()}" title="{$lang.validation_criteria}">
                                    <img src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/page_error.png" />
                                </a>
                            {/if}
                        </legend>
                        {include file=$tpldir|cat:'validation/tableAnexos.tpl'}
                    </fieldset>
                {/foreach}
            </div>


            <input type="hidden" name="m" value="{$element->getModuleName()}" />
            <input type="hidden" name="o" value="{$element->getUID()}" />
            <input type="hidden" name="send" value="1" />
            <input type="hidden" name="poid" value="{$document->getUID()}" />
            <input type="hidden" name="module" value="{$fileId->getModule()}" />
            <input type="hidden" name="fileId" value="{$fileId->getUID()}" />
            {if isset($validation)}
                <input type="hidden" name="validation" value="{$validation->getUID()}" />
            {/if}

            <div id="sub-menu-button">
                {if $fileId->fromHistory() }
                    <span style="font-size: 12px;">{$lang.moved_history}</span>
                    <br>
                {elseif $tab != $audit}
                    {if $tab == $review}
                        <ul>
                            <li>
                                <button type="submit" class="button yellow m refresh" data-text="{$lang.cargando}...">{$lang.siguiente}</button>
                            </li>
                        </ul>
                    {else}
                        {if count($anexos) }
                            <div class="validation-cont info">
                                <div class="padded" style="text-align: center;">
                                    <a class="slideToggle" data-target="#change-date">Cambiar fecha del documento y/o fecha de caducidad</a>
                                </div>
                                <div id="change-date" class="async-info-load once" style="display:none" href="../agd/validation/updateDate.php?fileId={$fileId->getUID()}&amp;tab={$tab}"></div>
                            </div>

                            <ul>
                                <li>
                                    <button type="submit" class="button red m detect-click send" name="validate"  data-text="{$lang.cargando}..." value="anular" rel='blank'  data-must="#commentValidator" data-alert="{$lang.cannot_be_empty_comment}">{$lang.anular_siguiente}</button>
                                </li>
                                <li>
                                    <button type="submit" class="button green m detect-click send" name="validate" data-text="{$lang.cargando}..." value="validar">{$lang.validar_siguiente}</button>
                                </li>
                            </ul>
                        {else}
                            <span style="float:center">{$lang.no_request_found}</span>
                        {/if}
                    {/if}
                {/if}

                {if $tab == $audit}
                    {if $validation->isRejected()}
                        {assign var="buttonAuditOk" value="Bien anulado"}
                        {assign var="buttonAuditWrong" value="Mal anulado"}
                        <br>
                        <span style="font-size: 14px;">Estás auditando una validación <strong>anulada</strong></span>
                    {else}
                        {assign var="buttonAuditOk" value="Bien validado"}
                        {assign var="buttonAuditWrong" value="Mal validado"}
                        <br>
                        <span style="font-size: 14px;">Estás auditando una validación <strong>aprobada</strong></span>
                    {/if}

                    <ul>
                        <li>
                            <button type="submit" class="button red m detect-click send" name="validate"  data-text="{$lang.cargando}..." value="audit_wrong">{$buttonAuditWrong}</button>
                        </li>
                        <li>
                            <button type="submit" class="button green m detect-click send" name="validate" data-text="{$lang.cargando}..." value="audit_ok">{$buttonAuditOk}</button>
                        </li>
                    </ul>
                {/if}
            </div>
        </form>

        {if isset($validators) && count($validators)>1 && $tab != $review }
            <hr />
            <form id="setuser" method="post" action="validation/setuser.php?poid={$fileId->getUID()}&amp;m={$element->getModuleName()}&amp;tab={$tab}" class="async-form">

                <select name="validator" onchange="$(this.form).submit();">
                    {foreach from=$validators item=validator}
                        <option value="{$validator->getUID()}" {if $validator->compareTo($user)}selected{/if}>{$validator->getUserVisibleName()}</option>
                    {/foreach}
                </select>
            </form>
        {/if}
    {/if}
{else}
    <div id="validation-no-document">
        {if $tab == $normal}
            {$lang.no_documents_pending_validate}
        {elseif $tab == $audit}
            No hay documentos pendientes de auditar.
        {else}
            {$lang.no_documents_pending_validate_urgent}
        {/if}
    </div>
{/if}
