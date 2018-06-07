	{if !isset($prefix)} {assign var="prefix" value=""} {else} {assign var="prefix" value=$prefix|lower|replace:' ':'-'} {/if}

	{if is_traversable($disponibles) && count($disponibles)}
		{assign var="seccion" value=$disponibles[0]->obtenerAgrupamientoPrimario()}
	{elseif is_traversable($asignados) && count($asignados)}
		{assign var="seccion" value=$asignados[0]->obtenerAgrupamientoPrimario()}
	{elseif isset($agrupamiento)}
		{assign var="seccion" value=$agrupamiento}
	{/if}

	{if isset($seccion)}
		{assign var="tabcount" value=0}
		<div style="text-align: center">

			<div class="cbox-content">
				{if isset($title)}
					<div style="text-align:left">
						<h1>{$title}</h1>
					</div>
					<hr />
				{/if}
				{if isset($carpeta)}  {* para usar en la asignacion de visibilidad de carpetas *}
				  <div style="text-align:left">
						<h1>{$lang.usuarios_con_visibilidad_de} {$carpeta->getUserVisibleName()}</h1>
					</div>
					<hr />
				{/if}
				<table class="asignar" style="width: 100%;">
						<thead>
							<tr>
								{if !$al_vuelo}
									<th > <a class="light checkall" target="#{$prefix}elementos-disponibles-all">{$lang.marcar_desmarcar}</a> {$lang.disponibles} </th> 
									<th style="width: 9%;"> </th>
								{/if}
								<th {if $al_vuelo}style="text-align: left; line-height: 0.9em;"{/if}> 
									{if !$al_vuelo} 
										<a class="light checkall" target="#{$prefix}elementos-asignados-all">{$lang.marcar_desmarcar}</a>  {$lang.asignados}
									{else}
										<input type="text" class="fast-add" maxlength="60" prefixasignados="{$prefix}elementos-asignados-all" prefixdisponibles="{$prefix}elementos-disponibles-all" target="#{$prefix}elementos-asignados-all" href="fastadd.php?m=agrupamiento&poid={$seccion->getUID()}&mode=agrupador&o={$elemento->getUID()}&assign={$elemento->getModuleName()}&rel={$agrupadorrelacion->getUID()}"/> {$lang.opt_crear_nuevo} 
									{/if}
								</th>
							</tr>
						</thead>
						<tr>
							{if !$al_vuelo}
								<td class="field-list">
									<ul id="{$prefix}elementos-disponibles-all">
										{if is_traversable($disponibles) && count($disponibles)}
											{foreach from=$disponibles item=item}
											<li>
												<label for="{$prefix}lbl-{$item->getUserVisibleName()}">
													<input type="hidden" name="{$prefix}elementos-disponibles[]" value="{$item->getUID()}" />
													<input type="checkbox" class="line-assign" id="{$prefix}lbl-{$item->getAssignName()}"/> 
													<span class="ucase">{$item->getAssignName()}</span>
												</label>
											</li>
											{/foreach}
										{/if}
									</ul>
								</td>
								<td style="border: 0px;">
									<button class="btn list-move" style="margin-bottom: 2px;" rel="#{$prefix}elementos-asignados-all" target="#{$prefix}elementos-disponibles-all">
										<span><span> &nbsp; &laquo; &nbsp; </span></span>
									</button>
									<br />
									<button class="btn list-move" rel="#{$prefix}elementos-disponibles-all" target="#{$prefix}elementos-asignados-all" >
										<span><span> &nbsp; &raquo; &nbsp; </span></span>
									</button> 
								</td>
							{/if}
							<td class="field-list  {if $al_vuelo}single{/if}">
								<ul id="{$prefix}elementos-asignados-all">
									{if is_traversable($asignados) && count($asignados)}
										{foreach from=$asignados item=item}
											<li id="id-li-{$item->getUID()}" name="{$item->getUID()}">
												<label for="{$prefix}lbl-{$item->getAssignName()}">
													{if $al_vuelo}
														<span class="relation-options">
															<span class="update" target="#{$prefix}val-elementos-asignados-{$item->getUID()}" rel="name" update="{$prefix}elementos-disponibles[]">
																<img src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/delete.png" title="{$lang.eliminar}" class="toggle" target="#id-li-{$item->getUID()}" />
															</span>
														</span>
													{/if}

													<input type="hidden" id="{$prefix}val-elementos-asignados-{$item->getUID()}" name="{$prefix}elementos-asignados[]" value="{$item->getUID()}" />
													<input type="checkbox" class="line-assign" id="{$prefix}lbl-{$item->getUserVisibleName()}"/> 
													<span class="ucase">{$item->getAssignName()}</span>
												</label>
											</li>
										{/foreach}
									{/if}
								</ul>
							</td>
						</tr>
						<tr><td {if !$al_vuelo}colspan="3"{/if} style="border: 0px"></td></tr>
				</table>

				{if isset($acciones) && is_array($acciones)}
					<div class="message highlight" style="text-align: center; width: auto;">
					{foreach from=$acciones item=accion}
						{assign var="string" value=$accion.string}
						<a class="{if isset($accion.class)}{$accion.class}{else}box-it{/if}" {if isset($accion.href)}href="{$accion.href}"{/if}>{if isset($lang.$string)}{$lang.$string}{else}{$string}{/if}</a>
					{/foreach}
					</div>
				{/if}
			</div>
		</div>
	{else}
		<div class="padded">
			No hay elementos para asignar
		</div>
	{/if}
