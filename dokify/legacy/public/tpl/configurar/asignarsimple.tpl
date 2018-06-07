
<div class="box-title">
	{$lang.asignar} {if isset($titulo)}{$titulo}{/if}
</div>
<form name="asignar-elementos" action="{$smarty.server.PHP_SELF}" class="form-to-box asistente" id="asignar-elementos" method="POST" style="width: 770px;">
	<div style="text-align: center;">
		{include file=$errorpath }
		{include file=$succespath }
		{include file=$infopath }

		<div class="cbox-content">
			{if isset($title)}
				<div style="text-align:left">
					<h1>{$title}</h1>
				</div>
				<hr />
			{/if}
			{if isset($carpeta)}{* para usar en la asignacion de visibilidad de carpetas | EDITADO JOSE: ARREGLA ESTO SI TIENES TIEMPO *}
			  <div style="text-align:left">
					<h1>{$lang.usuarios_con_visibilidad_de} {$carpeta->getUserVisibleName()}</h1>
				</div>
				<hr />
			{/if}
			<div style="text-align: left; padding-bottom: 12px;">
				{$lang.buscar} <input type="text" style="width: 65%" class="find-html" rel="li" target="#elementos-disponibles-all, #elementos-asignados-all" search="{$lang.buscando_elemento_s}" />
			</div>
			<table class="asignar" style="">
					<thead>
						<tr>
							<th > <a class="light checkall" target="#elementos-disponibles-all">{$lang.marcar_desmarcar}</a> {$lang.disponibles} </th> 
							<th style="width: 9%;"> </th>
							<th > <a class="light checkall" target="#elementos-asignados-all">{$lang.marcar_desmarcar}</a> {$lang.asignados} </th>
						</tr>
					</thead>
					<tr>
						<td class="field-list" style="width: 45%">
							<ul id="elementos-disponibles-all">
								{if is_traversable($disponibles) && true === is_countable($disponibles) && count($disponibles)}
									{foreach from=$disponibles item=disponible}
									{if isset($groupby)}

										{if is_traversable($groupby)}
											{assign var="method" value=$groupby}
											{if array_unshift($method, $disponible)}
												{if $group=call_user_func($method)}{/if}
											{/if}
										{else}
											{assign var="group" value=$disponible->obtenerDato($groupby)}
										{/if}


										{if (!isset($lastGroup) || $group!=$lastGroup) && trim($group)}
											{assign var="lastGroup" value=$group}
											{assign var="groupID" value="group_"|cat:$group|replace:' ':'_'|replace:'(':''|replace:')':''|replace:':':''|replace:'.':''|replace:',':''|replace:'/':''|lower}
											<li class="group" id="disponibles_{$groupID}"> {$group} </li>
										{/if}
									{/if}

									<li {if isset($group)}rel="{$groupID}"{/if} style="padding-right: {if true === is_countable($controls) && print count($controls)*25}{/if}px;" class="statusgreen">
										<div style="float: right; margin-right: 5px;">
											{foreach from=$controls item=control}
												{assign var="method" value=$control.method}
												{assign var="value" value=$elemento->$method($disponible) }
												<input type="{$control.type}" name="{$control.name}[{$disponible->getUID()}]" size="{$control.size|default:0}" value="{$value|default:0}"/>
											{/foreach}
										</div>
										<label for="lbl-dispoinible-{$disponible}">
											<input type="hidden" name="elementos-disponibles[]" value="{$disponible->getUID()}" />
											<input type="checkbox" class="line-assign" id="lbl-dispoinible-{$disponible}"/> 
											<span class="ucase" style="display: inline-block; width: 85%; margin-left: 22px">
												{if $disponible instanceof solicitable }
												{assign var="estado" value=$disponible->getStatusImage($user) }
												{if $estado.src != ""}<img src="{$estado.src}" title="{$estado.title}" height="12" width="12" style="padding: 0px; margin: 0px;">{/if}
												{/if}
												{$disponible->getAssignName($user,$elemento)}
											</span>
											{if $disponible instanceof solicitable }
											<span class="relation-options">
												<a href="#documentos.php?m={$disponible|get_class}&poid={$disponible->getUID()}" class="unbox-it"><img src="/res/img/famfam/folder.png" title="{$lang.documentos}" /></a>
											</span>
											{/if}
										</label>
									</li>
									{/foreach}
								{/if}
							</ul>
						</td>
						<td style="border: 0px;">
							<button class="btn list-move" style="margin-bottom: 2px;" rel="#elementos-asignados-all" target="#elementos-disponibles-all"><span><span> &nbsp; &laquo; &nbsp; </span></span></button><br /><button class="btn list-move" rel="#elementos-disponibles-all" target="#elementos-asignados-all" ><span><span> &nbsp; &raquo; &nbsp; </span></span></button> 
						</td>
						<td class="field-list">

							<ul id="elementos-asignados-all">
								{if is_traversable($asignados) && true === is_countable($asignados) && count($asignados)}
									{assign var="lastGroup" value=null}
									{foreach from=$asignados item=asignado}
										{if isset($groupby)}

											{if is_traversable($groupby)}
												{assign var="method" value=$groupby}
												{if array_unshift($method, $asignado)}
													{if $group=call_user_func($method)}{/if}
												{/if}
											{else}
												{assign var="group" value=$asignado->obtenerDato($groupby)}
											{/if}

											{if (!isset($lastGroup) || $group!=$lastGroup) && trim($group)}
												{assign var="lastGroup" value=$group}
												{assign var="groupID" value="group_"|cat:$group|replace:' ':'_'|replace:'(':''|replace:')':''|replace:':':''|lower|md5}
												<li class="group" id="asignados_{$groupID}"> {$group} </li>
											{/if}
										{/if}
										<li {if isset($group)}rel="{$groupID}"{/if}>
											<label for="lbl-asignado-{$asignado}">
												<div style="float: right; margin-right: 5px;">
													{foreach from=$controls item=control}
														{assign var="method" value=$control.method}
														{assign var="value" value=$elemento->$method($asignado) }
														<input type="{$control.type}" name="{$control.name}[{$asignado->getUID()}]" size="{$control.size|default:0}" value="{$value|default:0}"/>
													{/foreach}
												</div>
												<input type="hidden" name="elementos-asignados[]" value="{$asignado->getUID()}" />
												{if $asignado->lock}
													<img src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/lock_delete.png" class="item" title="{$lang.bloqueado}" />
												{else}
													<input type="checkbox" class="line-assign" id="lbl-asignado-{$asignado}"/> 
												{/if}
											<span class="ucase" style="display: inline-block; width: 85%; margin-left: 22px">
												{if $disponible instanceof solicitable }
												{assign var="estado" value=$asignado->getStatusImage($user) }
												{if $estado.src != ""}<img src="{$estado.src}" title="{$estado.title}" height="12" width="12" style="padding: 0px; margin: 0px;">{/if}
												{/if}
												{$asignado->getAssignName($user,$elemento)}
											</span>
												{if $disponible instanceof solicitable }
												<span class="relation-options">
													<a href="#documentos.php?m={$disponible|get_class}&poid={$asignado->getUID()}" class="unbox-it"><img src="/res/img/famfam/folder.png" title="{$lang.documentos}" /></a>
												</span>
											{/if}
											</label>
										</li>
									{/foreach}
								{/if}
							</ul>
						</td>
					</tr>
					<tr><td colspan="3" style="border: 0px"></td></tr>
			</table>

			{if isset($campos) && true === is_countable($campos) && count($campos)}
				<hr />
				{include file=$tpldir|cat:'form/form_table.inc.tpl'}
			{/if}

			{if isset($acciones) && is_array($acciones)}
				<div class="message highlight" style="text-align: center; width: auto;">
				{foreach from=$acciones item=accion}
					{assign var="string" value=$accion.string}
					<a class="{if isset($accion.class)}{$accion.class}{else}box-it{/if}" {if isset($accion.href)}href="{$accion.href}"{/if}>{if isset($lang.$string)}{$lang.$string}{else}{$string}{/if}</a>
				{/foreach}
				</div>
			{/if}

			<input type="hidden" name="send" value="1" />
			{if isset($smarty.request.poid)}<input type="hidden" name="poid" value="{$smarty.request.poid}" />{/if}
			{if isset($smarty.request.o)}<input type="hidden" name="o" value="{$smarty.request.o}" />{/if}
			{if isset($smarty.request.oid)}<input type="hidden" name="oid" value="{$smarty.request.oid}" />{/if}
			{if isset($smarty.request.m)}<input type="hidden" name="m" value="{$smarty.request.m}" />{/if}
			{if isset($smarty.request.comefrom)}<input type="hidden" name="comefrom" value="{$smarty.request.comefrom}" />{/if}
			{if isset($smarty.request.selected)}
				{foreach from=$smarty.request.selected item=seleccionado}
					<input type="hidden" name="selected[]" value="{$seleccionado}" />
				{/foreach}
			{/if}
		</div>
	</div>
	<div class="cboxButtons">
		{if isset($back)}
			<div style="float:left">
				<button class="btn box-it" href="{$back}"><span><span>{$lang.volver}</span></span></button>
			</div>
		{/if}
		<button class="btn" type="submit"><span><span> {$lang.asignar} </span></span></button>
	</div>
</form>
