<div class="box-title">
	{$title}
</div>
<form name="elemento-form-aptitud" action="{$smarty.server.PHP_SELF}" class="form-to-box" method="GET" id="elemento-form-aptitud">
	<div style="text-align: center; width:600px" >
		{include file=$errorpath}
		{include file=$succespath}
		{include file=$infopath}
		{if !isset($error)}
			<div class="message highlight cbox-content" style="text-align: left;">
				{$message}
			</div>
			<div class="cbox-content">			
				<table style="table-layout: auto;">
					<tr>
						<td class="form-colum-description"> {$lang.activar_apto} </td>
						<td class="form-colum-separator"></td>
						<td class="form-colum-value" style="vertical-align: middle;">
							<input name='suitable' type="checkbox" class="iphone-checkbox" {if $suitable} checked {/if}></input>
						</td>
					</tr>
				</table>
			</div>
		{/if}
	</div>
	<input type="hidden" name="oid" value="{$smarty.get.oid}" />
	<input type="hidden" name="m" value="{$smarty.get.m}" />
	<input type="hidden" name="send" value="1" />
	<div class="cboxButtons">
		{if !isset($error)}
			<button class="btn" type="submit"><span><span><img src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/disk.png" /> {$lang.guardar}</span></span></button>
		{/if}
		<button class="btn link" href="{$return}" {if !isset($error)}style="float:left;"{/if}><span><span style="white-space:nowrap;"><img src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/arrow_undo.png" />&nbsp; {$lang.volver}</span></span></button>
	</div>
</form>