<div>
	<div class="box-title">
		Cambios en AGD - r{$log.0_attr.revision}
	</div>
	<div style="height: 350px; width: 700px; overflow-y:auto">
		<div class="cbox-content">
			<table class="item-list">
				<tbody>
					{foreach from=$log item=entry key=i}
						{if is_numeric($i)}
							{assign var="date" value=$entry.date|strtotime}
							<tr>
								<td style="padding: 5px; vertical-align:top;">{$entry.author}</td> 
								<td style="padding: 5px; vertical-align:top; white-space:nowrap;">{$date|date_format:"%D %T"}</td> 
								<td style="padding: 5px;"><strong>{$entry.msg|trim|nl2br}</strong></td>
							</tr>
						{/if}
					{/foreach}
				</tbody>
			</table>
		</div>
	</div>	
	<div class="cboxButtons">
		<button class="btn"><span><span> <img src="{$resources}/img/famfam/add.png" /> Cargar mas</span></span></button>
	</div>
</div>
