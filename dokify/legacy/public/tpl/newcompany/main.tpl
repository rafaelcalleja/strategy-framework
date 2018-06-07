<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <meta http-equiv="X-UA-Compatible" content="chrome=1" />

        <link rel="shortcut icon" href="{$smarty.const.RESOURCES_DOMAIN}/img/favicon.ico" />
        <link rel="icon" href="{$smarty.const.RESOURCES_DOMAIN}/img/favicon.ico" />
        <link type="text/css" rel="stylesheet" href="{$smarty.const.RESOURCES_DOMAIN}/css/external.css?{$smarty.const.VKEY}" />
        <link type="text/css" rel="stylesheet" href="{$smarty.const.RESOURCES_DOMAIN}/css/registerCompany.css?{$smarty.const.VKEY}" id="main-style" />
        <link rel="stylesheet" href="{$smarty.const.RESOURCES_DOMAIN}/css/www/estilopassword.css" type="text/css" />
         <link rel="stylesheet" href="{$smarty.const.RESOURCES_DOMAIN}/css/chosen.css" />

        <script type="text/javascript">window.__rversion='{$smarty.const.VKEY}';</script>
        <script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.3/jquery.min.js"></script>

        {if !is_ie()}<script src="{$smarty.const.RESOURCES_DOMAIN}/js/chosen/chosen.jquery.min.js" type="text/javascript"></script>{/if}

        <!--[if IE]><link type="text/css" rel="stylesheet" href="{$resources}/css/ie/iehack.css" /><![endif]-->

        <title>dokify - {$lang.signup}</title>
    </head>
    <body>
        <div style="width:960px;margin:0 auto;">
            <div style="padding:20px 10px 25px;border-bottom:1px solid #F1F1F1;margin-bottom:10px">
                <img src="{$smarty.const.RESOURCES_DOMAIN}/img/dokify-google-logo.png" style="float:right" alt="dokify-logo" />
                <h1>{$lang.signup_dokify}</h1>
            </div>

            {if isset($invalidToken) || isset($errorSignup)}

                {if isset($invalidToken)}
                    <h1>{$lang.invalid_token_invitation}</h1>
                    <h2>{$lang.invalid_token_expl}</h2>
                    <h3>{$lang.invalid_token_zendesk}</h3>
                {/if}

                {if isset($errorSignup)}
                    <h1>{$lang.problem_during_signingup}</h1>
                    <br/><br/>
                    {if $error}
                        {if isset($lang.$error)}
                            {$lang.error_sigingup_cause} {$lang.$error}
                        {else}
                            {$lang.error_sigingup_unknown_cause}
                        {/if}
                    {else}
                        {$lang.error_sigingup_unknown_cause}
                    {/if}
                {/if}

            {else}
                <div class="menu-steps">
                    <div class="{if $step==1}active-step{/if} first {if $step > 1 || $step =="5" }completed-step{/if}"  ><a href="{if $step > 1}new.php{else}#{/if}"> <span>{$lang.usuario}</span></a></div>
                    <div {if $step==2}class="active-step"{/if} {if $step > 2 || $step =="5" }class="completed-step"{/if}><a href="{if $step > 2}new.php?step=2{else}#{/if}"><span>{$lang.location}</span></a></div>
                    <div {if $step==3}class="active-step"{/if} {if $step > 3 || $step =="5" }class="completed-step"{/if}><a href="{if $step > 3}new.php?step=3{else}#{/if}"><span>{$lang.empresa}</span></a></div>
                    <div {if $step==4}class="active-step"{/if} {if $step > 4 || $step =="5" }class="completed-step"{/if}><a href="{if $step > 4}new.php?step=4{else}#{/if}"><span>{$lang.license}</span></a></div>
                    <div {if $step =="5" }class="completed-step"{/if}><a href="#"><span>{$lang.finish}</span></a></div>
                </div>

                {assign var="path" value="newcompany/step_"|cat:$step|cat:".tpl"}
                {include file=$tpldir|cat:$path}
            {/if}
            </div>

        <script src="{$smarty.const.RESOURCES_DOMAIN}/js/pschecker/pschecker.js?{$smarty.const.VKEY}" type="text/javascript"></script>
        <script data-main="{$smarty.const.RESOURCES_DOMAIN}/js/signin.js" src="{$smarty.const.RESOURCES_DOMAIN}/js/require.js"></script>

    </body>
</html>