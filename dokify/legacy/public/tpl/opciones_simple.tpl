{*
Descripcion
	Plantilla simple orientada a selectores via json, usada con getHTML()

En uso actualmente
	-	

Variables
	· $columnas - int = numero de columnas de las opciones
	· $secciones - Array Ejemplo: (
		array("nombre" => "Selecciona las etiquetas visibles", "items" => array(
			array( "href" => "/agd/plugin/sigc/asignaretiqueta.php", "class" => "box-it", "lang" => "etiquetas" )
		)
	)
*}
	<div>
		{if isset($title)}
			<div class="option-title">{$title}</div>
		{/if}

		{if !isset($columnas)}
			{assign var="columnas" value=1}
		{/if}
		<table style="width: 100%;" class="big-options">
			{assign var="bucle" value=1}
			{foreach from=$secciones item=seccion key=i }
				{if ($bucle==1)}<tr>{/if}
					<td {if ((count($secciones)-$columnas)<$i) && ((count($secciones)-1)==$i)} colspan="{$columnas-$bucle+1}"{/if} class="column-option">
						{if isset($seccion.nombre)}
							<div class="section-title">
								<span class="ucase">{$seccion.nombre}</span>
							</div>
							<br />
						{/if}
						{foreach from=$seccion.items item=item }
							{if isset($item.options) && is_traversable($item.options)}
								<form action="{$item.href}" method="POST" {if isset($item.class)}class='{$item.class}'{/if}>
									<div class="message highlight">
										{if isset($lang[$item.lang])}
											{$lang[$item.lang]}
										{else}
											{$item.lang}
										{/if}
										<hr />
							{else}
								<a href="{$item.href}" {if isset($item.class)}class='{$item.class}'{/if}>
									<div class="message highlight">
										{if isset($lang[$item.lang])}
											{$lang[$item.lang]}
										{else}
											{$item.lang}
										{/if}
									</div>
								</a>
							{/if}
								{if isset($item.options) && is_traversable($item.options)}
									{foreach from=$item.options item=list key=listname}
										<div>
											Seleccionar {$listname}
											<select name="{$listname}">
												{foreach from=$list item=option}
													<option value="{$option->getUID()}">{$option->getUserVisibleName()}</option>
												{/foreach}
											</select>
										</div>
									{/foreach}
									<div style="float:right; margin-top:1em;">
										<button class="btn confirm"><span><span>{$lang.continuar}</span></span></button>
									</div>
								</div>
								</form>
							{/if}
						{/foreach}
						<hr />
					</td>
				{if ($bucle==$columnas)}
					{assign var="bucle" value=1}
					<tr>
				{else}	
					{assign var="bucle" value=$bucle+1} 
				{/if}
			{/foreach}
		</table>
	</div>
