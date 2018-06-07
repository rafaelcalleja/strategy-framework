<table class="item-list" style="width:100%; margin:0px 0px 15px 0px;">
    <tr class="strong">
        <td> {$lang.solicitante} </td>
        <td> {$lang.caducidad} </td>
        <td> {$lang.duracion}</td>
        <td> {$lang.documento_ejemplo}</td>
        <td> {$lang.estado} </td>
        <td> </td>
    </tr>

    {foreach from=$anexosCompany item=anexoItem }
        {assign var="atributo" value=$anexoItem->obtenerDocumentoAtributo()}
        {assign var="solicitud" value=$anexoItem->getSolicitud()}
        {assign var="infodoc" value=$anexoItem->getInfo()}
        {assign var="duracion" value=$atributo->obtenerDuraciones()}
        {assign var=attachUser value=$anexoItem->getUploaderUser()}
        {if $attachUser && $attachUser->exists()}
            {assign var=expirationTimezone value=$attachUser->getTimezone()}
            {assign var=utcExpiration value=false}
        {else}
            {assign var=expirationTimezone value=0}
            {assign var=utcExpiration value=true}
        {/if}
        {assign var="caducidad" value=$anexoItem->getExpirationTimestamp($expirationTimezone)}
        {assign var="originElement" value=$atributo->getElement()}

        <tr class="selected-row">
            <td>
                {if $solicitud }
                    {$solicitud->getRequestString()}
                {else}
                    -
                {/if}
            </td>

            <td>
                {if $caducidad > 0}
                    {$caducidad|date_format:"%d-%m-%Y"}{if $utcExpiration} (UTC){/if}

                    {if $atributo->caducidadManual()}
                        <span class="light" title="manual">(M)</span>
                    {/if}
                {else}
                    {$lang.no_caduca}
                {/if}
            </td>
            <td>
                {if $duracion > 0}
                    {$atributo->obtenerDuraciones(true)}
                {/if}
            </td>
            <td>
                {if $solicitud }
                    {assign var="docExample" value=$atributo->obtenerDocumentoEjemplo()}
                    {assign var="exampleRequest" value=$solicitud->getExampleRequest()}
                    {if false !== $docExample
                        && false !== $exampleRequest
                        && true === $docExample->isLoaded()
                        && $docExample->obtenerDato("descargar")
                    }
                        {assign var="exampleRequestUid" value=$exampleRequest->getUID()}
                        {assign var="requestUid" value=$solicitud->getUID()}
                        {assign var="infoExample" value=$docExample->getFileInfo($element, true)}
                        {assign var="urlPreviewModalExample" value='/app/request/'|cat:$exampleRequestUid|cat:'/preview/template?calledFromValidation=true&upload='|cat:$requestUid|urlencode}
                        {assign var="urlPreviewExample" value='/app/company/'|cat:$company->getUID()|cat:'?modal='|cat:$urlPreviewModalExample}
                        {assign var="urlDownloadExample" value='../agd/descargar.php?poid='|cat:$document->getUID()|cat:'&amp;o='|cat:$element->getUID()|cat:'&amp;oid='|cat:$infoExample.uid_anexo|cat:'&amp;m='|cat:$element->getType()|cat:'&descargable=true&action=dl&context=attach&calledFromValidation=true&comefrom='|cat:$requeriment}
                        <a target="_blank" href="{$urlPreviewExample}">{$lang.previsualizar}</a><span> - </span><a target="_blank" href="{$urlDownloadExample}">{$lang.descargar}</a>
                    {/if}
                {/if}
            </td>
            <td>
                {if $solicitud }
                    {$solicitud->getHTMLStatus()}
                {else}
                    -
                {/if}

            </td>

            {if $originElement instanceof agrupador}
                <td class="option">
                    <a class="slideToggle" data-target="#employee-{$anexoItem->getUID()}, #machine-{$anexoItem->getUID()}">
                        <img src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/application_put.png" title="{$lang.mostrar_todos}" />
                    </a>
                </td>

            {else}
                <td> </td>
            {/if}
        </tr>
        {if $uploaderCompany}
            <tr class="empty">
                <td colspan="6" class="company-items">

                    <table style="width:100%"><tr>
                        <td id="employee-{$anexoItem->getUID()}" class="async-info-load once" style="display:none" href="validation/assignments.php?agrupador={$originElement->getUID()}&modulo=empleado&poid={$uploaderCompany->getUID()}&m=empresa">

                        </td>
                        <td id="machine-{$anexoItem->getUID()}" class="async-info-load once" style="display:none" href="validation/assignments.php?agrupador={$originElement->getUID()}&modulo=maquina&poid={$uploaderCompany->getUID()}&m=empresa">

                        </td>
                    </tr></table>

                </td>
            </tr>

            {if $originElement instanceof agrupador && $uploaderCompany instanceof empresa}
                <tr ><tr/>
            {/if}
            {if $originElement instanceof agrupador && $uploaderCompany instanceof empresa}
                <tr ><tr/>
            {/if}
        {/if}
    {/foreach}
</table>