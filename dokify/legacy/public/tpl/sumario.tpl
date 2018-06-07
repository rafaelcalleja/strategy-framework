	{if isset($title)}
		<br />
			<div class="option-title">{$title}</div>
	{/if}
	<br />
	<table style="width: 100%"><tr>
		<td> 
			{if !count($info)}
				<div>
					No hay información
				</div>
			{/if}
			<div id="sumario">
				{foreach from=$info item=grupoinfo key=grname}
					<div class="bloque-info line-block">
						<div class="border">
							<h1>
								<span class="date"></span>
								{if isset($lang.$grname)}{$lang.$grname}{else}{$grname}{/if}
							</h1>
							<div class="bloque-body">
								<table>
								{foreach from=$grupoinfo item=value key=desc}
									<tr>
										<td>{if isset($lang.$desc)}{$lang.$desc}{else}{if is_string($desc)}{$desc}{/if}{/if}</td>
										{if is_string($desc)}<td style="width: 10px"></td>{/if}
										<td>
											{if is_string($value) || is_numeric($value) }
												<strong>{$value}</strong>
											{else}
												<strong><a href="{$value.href}">{$value.innerHTML}</a></strong>	
											{/if}
										</td>
									</tr>
								{/foreach}
								</table>
							</div>
						</div>
					</div>
				{/foreach}
			</div>
		</td>
		<td> &nbsp; </td>
		<td style="width: 1%;"> &nbsp; </td>
	</tr></table>
	<hr />
