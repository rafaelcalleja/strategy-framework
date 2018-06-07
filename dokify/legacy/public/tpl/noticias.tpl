	{if isset($title)}
		<br />
			<div class="option-title">{$title}</div>
	{/if}
	<br />
	<table style="width: 100%"><tr>
		<td> 
			{if !count($noticias)}

			{/if}
			{foreach from=$noticias item=noticia}
				<div class="bloque-noticias">
					<h1>
						<span class="date">{$noticia->getDate()}</span>
						{$noticia->getUserVisibleName()}
					</h1>
					<div>
						{if print(nl2br($noticia->getHTML()))}{/if}
					</div>
				</div>
			{/foreach}
		</td>
		<td> &nbsp; </td>
		{*
		<td style="width: 19%"> 
			<div class="bloque-noticias">
				<h1>Accesos Directos</h1>
				<p>
					<ul>
						<li> Acceso Directo #1 </li>
						<li> Acceso Directo #2 <li>
						<li> Acceso Directo #3 </li>
						<li> Acceso Directo #4 </li>
					</ul>
				</p>
			</div>
		</td>
		*}
		<td style="width: 1%;"> &nbsp; </td>
	</tr></table>
