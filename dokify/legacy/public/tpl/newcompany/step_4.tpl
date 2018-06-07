<div style="float:left;width:100%;padding:10px">
    <form action="?step=5" method="post">
        <h2>{$lang.license}</h2>
        <br />
        {if $pagoObligatorio}
            <div id="text-payment">

                {$lang.license_form_premium_expl}<br/><br/>

                {$lang.license_form_opt_one}<br/>
                {$lang.license_form_opt_two}<br/>
                {$lang.license_form_opt_three}<br/>
                {$lang.license_form_opt_four}<br/><br/>

                {$lang.license_form_detail_premium_plan}<br/><br/>

            </div>
        {else}
            <div id="text-payment">

                {$lang.license_form_free_expl}<br/><br/>

                {$lang.license_form_opt_one}<br/>
                {$lang.license_form_opt_two}<br/>
                {$lang.license_form_opt_three}<br/>
                {$lang.license_form_opt_four}<br/><br/>

                {$lang.license_form_detail_premium_plan}<br/><br/>

                {$lang.license_form_change_to_premium}<br/><br/>

            </div>
        {/if}

        <hr />

        <br />
        <div style="text-align:right">
            <div style="float:left">
                <input type="checkbox" id="terms" name="terms" onclick="{literal}$('#continue').toggle();{/literal}" style="width:auto; vertical-align: middle" checked /> {$lang.he_leido_acepto} <a href="/terminos-y-condiciones" target="_blank">{$lang.terminos_condiciones}</a>
            </div>

            <div style="float:right">
                <input type="hidden" name="send" value="1" />
                <button class="continue" id="continue">{$lang.siguiente}</button>
            </div>

            <div style="clear:both"></div>
        </div>
    </form>
</div>