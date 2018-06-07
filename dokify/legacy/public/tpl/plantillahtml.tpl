{*
Descripcion
	Se utiliza en modo simple o option, carga el editor de texto wywiwyg

En uso actualmente
	-	/configurar/plantillaemail/modificar.php
	-	/configurar/noticia/modificar.php

Variables
	· $action - if isset = donde se enviara el formulario?
	· $titulo - if isset = se muestra un campo de titulo para el texto
	· $inputs extra - if isset = html para renderizar
	· $html - if isset = html para renderizar
	
*}
<table width="100%" style="height: 100%;"><tr><td style="height: 100%;">
	<form class="form-with-editor" style="height: 100%;" action="{if isset($action)}{$action}{else}{$smarty.server.PHP_SELF}{/if}" {if isset($goto)}rel="{$goto}"{/if}>
		<div class="editor-extra-data">
			{if isset($titulo)}
				<span style="font-size: 14px;">Título</span> <input type="text" name="titulo" style="margin: 4px; width: 70%;" value="{$titulo|htmlspecialchars}"/>
			{/if}
			{if isset($inputs)}
				{foreach from=$inputs item=input key=i}
					{$input.innerHTML} <input type="{$input.type}" name="{$input.name}" {if isset($input.value)}value="{$input.value}"{/if} {if (isset($input.checked)&&$input.checked)||$input.value}checked{/if}
						{if $input.type=='checkbox'}onclick="this.value=(this.checked)?1:0;"{/if}
					/>
				{/foreach}
			{/if}
		</div>
		<table width="100%" style="height: 85%; margin-bottom: 8px;"><tr><td style="height: 100%;">
			<textarea name="contenido" id="editor" style="width: 100%; height: 550px;">
				{if isset($html)}{$html|htmlspecialchars}{/if}
			</textarea>
		</td></tr></table>
		{*
		<table width="100%" style="height: 15%;"><tr><td style="height: 100%;">
			<button class="btn send" ><span><span>Guardar esta plantilla</span></span></button>
		</td></tr></table>
		*}
		{if isset($smarty.get.poid)}<input type="hidden" name="poid" value="{$smarty.get.poid}" />{/if}
		<input type="hidden" name="send" value="1" />
	</form>
</td></tr></table>
