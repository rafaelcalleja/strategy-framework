<div class="box-title">{$lang.ayuda}</div>
<form action="{$smarty.server.PHP_SELF}" method="POST" class="form-to-box">
	{include file=$errorpath}
	{include file=$succespath}
	{include file=$infopath}

	{assign var=empresa value=$user->getCompany()}

	<div class="cbox-content" style="width: 600px">
		<div class="padded">
			{$lang.ayuda_texto}
		</div>
		<hr />
		<div style="text-align: center; font-size: 16px">
			{$lang.go_suport_dokify|sprintf:$user->getZendeskURL()}
		</div>
		<hr />
		<div class="padded">
			{$lang.texto_ayuda_guia}
		</div>
		<table>
			<tr> 
				<td class="padded" style="padding-right:100px; width: 20%">{$lang.telefono}</td>
				<td class="middle-td"></td>
				<td> 
					{if $empresa->isFree()}
						{$lang.llama_y_codigo_free} <strong class="margenize">{$codigo}</strong>
						<br />
						<span class="red">{$lang.contratar_premium_soporte} </span> <a href="/app/payment/license">{$lang.pincha_aqui}</a>
					{else}
						{$lang.llama_y_codigo_premium} <strong class="margenize">{$codigo}</strong>
					{/if}
				</td>
			</tr>
			<tr> 
				<td class="padded">{$lang.email}</td>
				<td class="middle-td"></td>
				<td><a href="mailto:{$lang.mail_soporte}" target="_blank">{$lang.mail_soporte}</a></td>
			</tr>
		</table>
		{if isset($smarty.request.return)}
			<hr />
			<div style="text-align: center">
				<a class="btn box-it" href="{$smarty.request.return}"><span><span>{$lang.volver}</span></span></a>
			</div>
		{/if}
	</div>
	<div class="cboxButtons">
		{*{if $user->esStaff()}<button href="ayuda.php?mode=chat" class="btn"><span><span>{$lang.help_chat|default:"Live Help"}</span></span></button>{/if}*}
	</div>
	<input type="hidden" name="send" value="1" />
</form>
