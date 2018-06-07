<div style="width: 550px">
	<div class="box-title">
		{$lang.referencia}
	</div>
	<form action="{$smarty.server.PHP_SELF}" class="form-to-box" method="post">

		{include file=$errorpath}
		{include file=$succespath}
		{include file=$infopath}
		
		<div class="cbox-content">
			
			<table><tr>
				{assign var="value" value='documento_atributo::REF_TYPE_NONE'|constant}
				<td class="padded" style="width: 30px;">
					<input type="radio" name="referenciar_empresa" value="{$value}" {if $value == $referencia}checked{/if} style="vertical-align:middle" />
				</td>
				
				<td style="vertical-align:middle">{$lang.expl_referenciar_no}</td>
				
			</tr></table>

			<hr />

			{if $targetModule != 'empresa'}
				<table><tr>
					{assign var="value" value='documento_atributo::REF_TYPE_COMPANY'|constant}
					<td class="padded" style="width: 30px;">
						<input type="radio" name="referenciar_empresa" value="{$value}" {if $value == $referencia}checked{/if} style="vertical-align:middle" />
					</td>
					
					<td style="vertical-align:middle">{$lang.expl_referenciar_empresa}</td>
					
				</tr></table>
				<hr />
			{/if}

			<table><tr>
				{assign var="value" value='documento_atributo::REF_TYPE_CHAIN'|constant}
				<td class="padded" style="width: 30px;">
					<input type="radio" name="referenciar_empresa" value="{$value}" {if $value == $referencia}checked{/if} style="vertical-align:middle" />
				</td>
				
				<td style="vertical-align:middle">{$lang.expl_referenciar_subcontratacion}</td>
				
			</tr></table>

			{if $targetModule == 'empresa'}
				<hr />

				<table><tr>
					{assign var="value" value='documento_atributo::REF_TYPE_CONTRACTS'|constant}
					<td class="padded" style="width: 30px;">
						<input type="radio" name="referenciar_empresa" value="{$value}" {if $value == $referencia}checked{/if} style="vertical-align:middle" />
					</td>
					
					<td style="vertical-align:middle">{$lang.expl_referenciar_contratacion}</td>
					
				</tr></table>
			{/if}
		</div>

		<input type="hidden" name="poid" value="{$smarty.request.poid}" />

		<div class="cboxButtons">
			<button class="btn" type="submit"><span><span><img src="{$resources}/img/famfam/add.png"> {$lang.guardar}</span></span></button>
		</div>
	</form>
</div>