{*
	Mostrar un select simple para recoger posteriormente
*}

<div class="box-title">
	{$lang.titulo_seleccionar}
</div>
<form name="seleccion" id="seleccion" method="POST" action="{$smarty.server.PHP_SELF}" class="form-to-box">
	{include file=$errorpath}
	{include file=$succespath}
	{include file=$infopath}
	<div class="cbox-content">

		{if is_array($elementos) && count($elementos)}
			{$lang.selecciona_un_elemento}
			<br /><br />
			<select name="oid" style="width: 90%" onchange="n=$(this.options[this.selectedIndex]).attr('name'); this.form.t.value=n;">
				<option>Seleccionar...</option>
				{foreach from=$elementos item=elemento key=nombre}
					{if is_object($elemento)}
						<option value="{$elemento->getUID()}" name="{$elemento->getType()}">{$elemento->getSelectName()}</option>
					{else}
						{if is_array($elemento)}
							<optgroup label="{$nombre}">
								{foreach from=$elemento item=subelemento key=sbname}	
									<option value="{$subelemento->getUID()}" name="{$subelemento->getType()}">{$subelemento->getSelectName()}</option>
								{/foreach}
							</optgroup>
						{/if}
					{/if}
				{/foreach}
			</select>
		{else}
			{$lang.select_sin_elementos}
		{/if}
	</div>
	<input type="hidden" name="t" value="" />
	<div class="cboxButtons">
		{if is_array($elementos) && count($elementos)}
			<button class="btn" type="submit"><span><span>{$lang.continuar}</span></span></button>
		{/if}
	</div>
	<input type="hidden" name="send" value="1" />
	{if isset($smarty.get.poid)}<input type="hidden" name="poid" value="{$smarty.get.poid}" />{/if}
	{if isset($smarty.get.m)}<input type="hidden" name="m" value="{$smarty.get.m}" />{/if}
</form>


