<div class="box-title">{$lang.request_urgent_validation}</div>


<form action="{$smarty.server.PHP_SELF}" method="POST" class="form-to-box">
    {include file=$errorpath}
    {include file=$succespath}
    {include file=$infopath}

    {assign var=empresa value=$user->getCompany()}

    <div class="cbox-content" style="width: 600px">
        <div class="padded center">
            {assign var=validationMinTime value='validation::MIN_TIME_VALIDATE'|constant}
            {assign var=validationMaxTime value='validation::MAX_TIME_VALIDATE_URGENT'|constant}

            {if isset($AVGValidation) && $AVGValidation > $validationMinTime && $AVGValidation < $validationMaxTime }
                {assign var=AVGTime value="util::secsToHuman"|call_user_func:$AVGValidation}
                {$lang.average_time_validation|sprintf:$AVGTime:"24"}

            {else}
                {$lang.urgent_validation_screen_exp}
            {/if}

            {$lang.cost_of_validation} <span class="text-big strong">{$urgentPrice} €</span>.<br><br>

            {if $canSelectItems && isset($documentName) && isset($elementName)}
                {$lang.validation_urgent_employee_expl|sprintf:$documentName:$elementName}<br><br>
            {/if}

            <span class="light">{$lang.he_leido_acepto} <a target="_blank" href="/terminos-y-condiciones">{$lang.terminos_y_condiciones}</a></span><br><br>
        </div>

    </div>
    <input type="hidden" name="send" value="1" />
    <input type="hidden" name="fileId" value="{$fileId}" />

    <div class="cboxButtons">
        <button class="btn cancel"  style="float:left"><span><span> <img src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/cancel.png" style="vertical-align:top"/> {$lang.cancelar} </span></span></button>

        <button class="btn" type="submit" style="float:right"><span><span> <img src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/accept.png" style="vertical-align:top"/> {$lang.solicitar} </span></span></button>
        <div style="clear:both"></div>
    </div>

</form>

