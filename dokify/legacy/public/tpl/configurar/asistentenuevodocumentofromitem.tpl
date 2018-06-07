{*{assign var="tpldir" value=$smarty.server.DOCUMENT_ROOT|cat:"/tpl/configurar/asistentenuevodocumento/" }*}
<div class="box-title">
    {$lang.asistente_nuevo_documento}
</div>
<form name="asistente-nuevo-documento" action="{$smarty.server.PHP_SELF}" method="post" class="form-to-box asistente" id="asistente-nuevo-documento" style="width: 600px;">
    {include file=$errorpath}
    {include file=$succespath}
    {include file=$infopath}

    <div class="cbox-content">
        <p>Crea una nueva solicitud de documentos para que se solicite a <strong>{$item->getUserVisibleName()}</strong> y a otros elementos <strong>similares</strong></p>
        <br />

        <fieldset style="padding:8px;">
            <legend><h3>1. Cuando se debe pedir el documento?</h3></legend>
            <select name="origin">
                {foreach from=$origins item=origin}
                    {assign var="string" value="cuando_solicitar_"|cat:$origin->getType()}
                    <option value="{$origin}">{$origin->getSelectName()|string_format:$lang.$string}</option>
                {/foreach}
            </select>
        </fieldset>
        <fieldset style="margin-top:10px;padding:8px;">
            <legend><h3>2. Que tipo de documento quieres solicitar?</h3></legend>
            <p>Selecciona el que mas se ajuste a tus necesidades</p>
            <select name="document">
                {foreach from=$documents item=document}
                    <option value="{$document.uid_documento}">{$document.nombre|trim}</option>
                {/foreach}
            </select>
        </fieldset>
        <fieldset style="margin-top:10px;padding:8px;">
            <legend><h3>3. Indica que características tendrá el documento</h3></legend>
            El documento será de obligado cumplimiento <input type="checkbox" name="mandatory" />
            <br />
            Si quieres que el documento caduque, indica cuantos días de validez tendrá a continuación: <input type="text" size="5" name="duration" />
        </fieldset>

    </div>
    <div class="cboxButtons">
        <button class="btn" type="submit"><span><span><img src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/add.png" /> {$lang.guardar}</span></span></button>
    </div>

    <input type="hidden" name="send" value="1" />
    {if isset($smarty.request.m)}<input type="hidden" name="m" value="{$smarty.request.m}" />{/if}
    {if isset($smarty.request.poid)}<input type="hidden" name="poid" value="{$smarty.request.poid}" />{/if}
</form>
