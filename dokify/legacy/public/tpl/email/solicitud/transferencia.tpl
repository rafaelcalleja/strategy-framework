{assign var=asunto value='asunto_email_solicitud_'|cat:$type|cat:'_'|cat:$estado}
{assign var=email value='email_solicitud_'|cat:$type|cat:'_'|cat:$estado}
<div style="padding:10px 20px 0 0">
	<img src="{$smarty.const.RESOURCES_DOMAIN}/img/dokify-google-logo.png" style="float: right" alt="logo-dokify" />
	<h1 style="margin-top:0">{$lang.$asunto}</h1>
	<p>{$item->getUserVisibleName()|string_format:$lang.$email}</p>
	{if $estado==2 && $motivo}<p>{$motivo|string_format:$lang.motivo_rechazo_solicitud}</b></p>{/if}
	{if $estado==1 || $estado==5}<p>{$item->obtenerUrlPreferida(true)|cat:''|string_format:$lang.puedes_comprobarlo_aqui}</p>{/if}
	{if $estado==0}<p>{$item->obtenerUrlPreferida(true)|cat:'&req='|cat:$solicitud->getUID()|string_format:$lang.enlace_responder_solicitud}</p>{/if}
	{include file=$smarty.const.DIR_ROOT|cat:'/tpl/email/pie.tpl'}
</div>
