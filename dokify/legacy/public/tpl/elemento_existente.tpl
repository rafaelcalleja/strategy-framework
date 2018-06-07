{*
    FORMULARIO PARA ODER ELEGIR QUE HACER CUANDO VAS A DAR DE ALTA UN ELEMENTO QUE YA EXISTE EN EL SISTEMA

    · $modulo = string para saber sobre que elemento estamos trabajando

*}
<div class="asistente">
    <div class="message highlight " style="margin: 15px 30px">
        {assign var="mensaje" value="mensaje_"|cat:$modulo|cat:"_existente"}
        {assign var="string_boton" value="alta_como_"|cat:$modulo}
        {assign var=userCompany value=$user->getCompany()}

        {$lang.$mensaje} <strong id="nombre-{$modulo}-existente">{$smarty.get.txt}</strong>
        <br /><br />
        <div style="text-align: right">
            <form method="get" name="elemento-form-exists" action="{if isset($smarty.request.back)}{$smarty.request.back}{else}{$modulo}/nuevo.php{/if}" class="form-to-box" id="elemento-form-exists" style="display: inline">
                <button class="btn"><span><span> {$lang.volver_al_formulario} </span></span></button>
                {if !isset($smarty.request.back)}
                    <input type="hidden" name="oid" id="oid" value="{$smarty.request.oid}" />
                    <input type="hidden" name="poid" id="poid" value="{$smarty.request.poid}" />
                {/if}
            </form>
            {if $userCompany->hasMachine($machine) === false}
                <form method="post" name="elemento-form-exists" action="{$modulo}/nuevo.php" class="form-to-box" id="elemento-form-exists" style="display: inline">
                    <button class="btn"><span><span> {$lang.$string_boton} </span></span></button>
                    <input type="hidden" name="send" value="1" />
                    <input type="hidden" name="oid" id="oid" value="{$smarty.get.oid}" />
                    <input type="hidden" name="poid" id="poid" value="{$poid}" />
                </form>
            {/if}
        </div>
    </div>
</div>
