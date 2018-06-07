<div class="box-title">
	{$lang.confirmar_cliente}
</div>
<form name="confirmar-cliente" action="{$smarty.server.PHP_SELF}" class="form-to-box" id="confirmar-cliente">
	<div style="text-align: center">
		{include file=$errorpath}
		{include file=$succespath}
		{include file=$infopath}
	</div>
	<div class="cbox-content" style="width: 450px;">
		{$lang.confirmar_cliente_texto} <strong id="nombre-empresa-existente">{$empresa->getUserVisibleName()}</strong>
		<hr />
		{$lang.quieres_reanexar} <input type="checkbox" name="reanexar" checked /><br />
		<hr />
		<div style="margin-bottom:20px">
			{if isset($typesCompany)}
				<div>{$lang.choose_category_client}</div>
			    <div style="margin-top:10px">
			    	<select name="tipo_empresa">
			    		<option value="">{$lang.selecciona}</option>
				    	{foreach from=$typesCompany item=type key=i}
				        	<option value="{$type->getUID()}">{$type->getUserVisibleName()}</option>
						{/foreach}
					</select>
				</div>
			{/if}
		</div>
		<button class="btn toggle" target="#comentar-documento"><span><span> <img src="{$resources}/img/famfam/user_comment.png" /> {$lang.comentario}</span></span></button>
		<div style="display:none" id="comentar-documento">
			<hr />
			<div class="cbox-content">
				{$lang.comentario}...
				<br />
				<textarea name='response_message' id="anexo-comentario"></textarea>
			</div>
		</div>
	</div>
	<div class="cboxButtons">
<!-- 		<button class="btn detect-click" name="action" value="cancel"><span><span> <img src="{$resources}/img/famfam/delete.png"> {$lang.denegar} </span></span></button> -->
		<button class="btn detect-click" name="action" value="accept"><span><span> <img src="{$resources}/img/famfam/arrow_right.png"> {$lang.aceptar} </span></span></button>
		<button class="btn detect-click" name="action" value="reject"><span><span> <img src="{$resources}/img/famfam/delete.png"> {$lang.denegar} </span></span></button>
		<input type="hidden" name="send" value="1" />
		<input type="hidden" name="poid" id="poid" value="{$smarty.get.poid}" />
	</div>	
</form>