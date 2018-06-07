<div class="box-title">
	{$lang.elemento_existente}
</div>
<form name="confirmar-transferencia" action="/agd/empresa/invite.php" class="async-form" id="confirmar-transferencia">
	<div class="cbox-content" style="width: 450px;">
		{assign var=msg value=$lang.mensaje_empresa_existente}
		{assign var=empresa value='<strong id="nombre-empresa-existente">'|cat:$smarty.get.txt|cat:'</strong>'}

		{$empresa|string_format:$msg}
		</br>
		</br>
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
		<div name="elemento-form-exists" class="form-to-box" id="elemento-form-exists" style="display: inline">
			<button class="btn"><span><span>{$lang.volver_al_formulario}</span></span></button>
			<input type="hidden" name="comefrom" id="comefrom" value="empresa" />
		</div>
		<div name="elemento-form-exists" class="form-to-box" id="elemento-form-exists" style="display: inline">
			<button class="btn detect-click" name="send" value="1"><span><span>  {$lang.alta_como_subcontrata}  </span></span></button> 
			<input type="hidden" name="oid" id="oid" value="{$smarty.get.oid}" />
			<input type="hidden" name="poid" id="poid" value="{$smarty.get.poid}" />
			<input type="hidden" name="m" id="m" value="empresa" />
		</div>
	</div>
</form>	

