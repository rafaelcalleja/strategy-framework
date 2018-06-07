<div class="cbox-content">
	<h1>Selecciona para que empresa se va a realizar el trabajo</h1>
	<br />
	
	{foreach from=$profiles item=profile}
		<div style="margin-bottom: 1em">
			<a href="#ficha.php?m=empleado&amp;poid={$elemento->getUID()}&amp;src=qr&amp;profile={$profile->getUID()}" class="button black xl" style="width:100%;text-align:center; padding:0.5em 0">{$profile->getUserVisibleName()}</a>
		</div>
	{/foreach}
</div>