<div style="color:grey; margin-top:25px;">
	{foreach from=$emailhistorico item=historico }
		{$historico.date|date_format:"%d/%m/%Y"} dokify &lt;{$from}&gt;
		<div style='border-left:1px #ccc solid; padding-left: 9px; margin-top:5px'>
			<strong>{$historico.subject|utf8_decode}</strong><br>
			{$historico.body|utf8_decode}
		</div>
	{/foreach}
</div>

