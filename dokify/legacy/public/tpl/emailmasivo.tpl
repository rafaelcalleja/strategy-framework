<div style="width: 600px">
	<div class="box-title">
		{$lang.enviar_email}
	</div>
	<form name="envio-email-masivo" action="{$smarty.server.PHP_SELF}" class="async-form agd-form" method="post" id="envio-email-masivo">
		<div style="text-align: center">
			{include file=$errorpath}
			{include file=$succespath}
			{include file=$infopath}
		</div>
		<div class="cbox-content">
			<h1>{$elemento->getUserVisibleName()}</h1>
			{if $contentDefined}
				<div class="framecontainer">
					<iframe src="plantillaemail.php?t={$plantilla}" frameborder="0"></iframe>
				</div>
			{/if}
			{if isset($inputs)}
				<div style="margin: 10px 0">
					<table>
						{foreach from=$inputs item=input}
							<tr>
								<td class="ucase" style="width: 20%; vertical-align:middle"> <strong>{$input.innerHTML}</strong> </td>
								<td></td>
								<td> 
									{if $input.tagName == 'select'}
										<select name="{$input.name}">
											{foreach from=$input.options item=text key=val}
												<option value="{$val}">{$text}</option>
											{/foreach}
										</select>
									{else}
										<input type="text" name="{$input.name}" value="{$input.value|default:''}" {if isset($input.placeholder)}placeholder="{$input.placeholder}"{/if} {if isset($input.className)}class="{$input.className}"{/if} /> 
									{/if}
								</td>
							</tr>
						{/foreach}
					</table>
				</div>
			{/if}
			{if isset($options)}
				<div>
					<ul>{foreach from=$options item=option}
						<li>
							<input type="checkbox" name="{$option.name}" /> {$option.innerHTML}
						</li>
					{/foreach}</ul>
				</div>
			{/if}
			<div>
				<textarea onchange="this.name='comentario';" id="email-comment" placeholder="Añadir un comentario..." {if !$contentDefined}rows="10"{/if}>{if isset($smarty.request.comentario)}{$smarty.request.comentario}{/if}</textarea>
			</div>

			{if isset($attachments)}
				<div class="beauty-file">
					<input type="file" name="file" class="text-attach" data-target="#email-comment" data-link="#attach-link" data-disclaimer="#attach-disclaimer" />
					<div>
						<a href="javascript:void(0);" id="attach-link">{$lang.adjuntar_fichero_correo}</a>
					</div>
				</div>

				<div id="attach-disclaimer" class="red light" style="visibility:hidden">
					{$lang.email_attachment_disclaimer}
				</div>
			{/if}
		</div>

		{if isset($smarty.request.poid)}<input type="hidden" name="poid" value="{$smarty.request.poid}" />{/if}
		<input type="hidden" name="send" value="1" />
		<div class="cboxButtons">
			<button class="btn confirm" type="submit"><span><span> <img src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/email_go.png" /> {$lang.$boton|default:$lang.continuar}</span></span></button>
		</div>
	</form>
</div>