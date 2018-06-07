{*

Variables
	· goto = string - donde ir cuando se envie el formulario
	· viewonly = if isset = quitar el boton de guardar y avisar
	· perfil = el objeto perfil | rol | cliente que es modificado
*}
{if !(count($usuario->obtenerAgrupamientosWithFilter())) && $usuario->isViewFilterByGroups()}
	<div class="keep-visible">
		<div class="elemento-status-bar elemento-status-red">
			{$lang.filter_group_user_acces}
		</div>
	</div>
{elseif !(count($usuario->obtenerEtiquetas())) && $usuario->isViewFilterByLabel()}
	<div class="keep-visible">
		<div class="elemento-status-bar elemento-status-red">
			{$lang.filter_label_user_acces}
		</div>	
	</div>
{/if}
<div class="p-left-seventy">
	</br>
	<form name="ver-perfiles" style="width: 100%" action="{$smarty.server.PHP_SELF}" class="async-form reload asistente" method="POST" id="ver-perfiles" {if isset($goto)}rel="{$goto}"{/if}>
		{* ES POSIBLE QUE EN VEZ DE UN PERFIL SE PASE UN CLIENTE O UN ROL *}
		{if is_callable(array($perfil,"getUser"))}
			{assign var="rolactual" value=$perfil->getActiveRol()}
			{assign var="usuarioPerfil" value=$perfil->getUser()}
		{/if}
		{if !isset($viewonly)}
			<div style="float: right; margin-right: 30px;">
				<button class="btn send" type="submit"><span><span>
						<img src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/disk.png" />
						{$lang.guardar}
						</span></span>
				</button>
			</div>
		{else}
			<div style="float: right; margin-right: 30px;">
				<div class="message error">No puedes modificar estos datos</div>
			</div>
		{/if}
		<h1 style="font-size: 17px">
			{$lang.configurar_perfil} <span style="color: red;">{$perfil->getUserVisibleName()}</span> {if isset($usuarioPerfil)} {$lang.del_usuario} <span style="color: red;">{$usuarioPerfil->getUserVisibleName()}</span>{/if}
		</h1>
		<hr />
		

		{* ES POSIBLE QUE EN VEZ DE UN PERFIL SE PASE UN CLIENTE *}
		{if is_callable( array($perfil,"obtenerOpcionesExtra") ) && !$perfil instanceof rol}
			<div>
				{assign var=opcionesPerfil value=$perfil->obtenerOpcionesExtra()}
				<div style="margin: 4px 0 0 0px;">
					<ul>
					{if $perfil instanceof perfil}
						<li>
							{assign var="infoAcceso" value=$user->getAvailableOptionsForModule("rol",21,0)}
							{if count($infoAcceso) }
								{*tipo=0*}
								{$lang.asignar_permisos_de_rol} &nbsp;
								<select id="lista-roles" name="rol" style="width:300px;font-size:11px">
									<option>-- {$lang.desc_aplicar_rol} --</option>
									{foreach from=$roles item=rol key=indice}
										<option value="{$rol->getUID()}"  {if ($rolactual instanceof rol)&&($rolactual->getUID()==$rol->getUID())}selected{/if}>{$rol->getUserVisibleName()}</option>
									{/foreach}
								</select>
								 &nbsp; {$lang.y_hacer_persistente} <input type="checkbox" class="toggle" target="#all-options-wrap" name="persitente" id="persistente" {if $rolactual instanceof rol}checked{/if}/>
								{*
								{if !isset($viewonly)}
								 	&nbsp;<button class="btn sendinput" href="configurar/usuario/editarperfil.php?action=rol&poid={$perfil->getUID()}" target="#lista-roles, #persistente"><span><span>{$lang.desc_aplicar_rol}</span></span></button>
								{/if}
								*}
							{/if}
							<hr />	
						</li>
					{/if}

					{foreach from=$opcionesPerfil item=value key=campo}
						{if isset($lang.$campo)}
							{assign var=opcionesOpcion value=$perfil->obtenerOpcionesRelacionadas($campo)}
							<li class="extra-option">
								{$lang.$campo} <input type="checkbox" name="{$campo}" {if $value}checked{/if}/>
								{if count($opcionesOpcion)}
									{foreach from=$opcionesOpcion item=subCampo key=i}
										{if isset($lang[$subCampo.name])}
											{$lang[$subCampo.name]} 
											{if $subCampo.tagName == "input"}
												<input type="{$subCampo.type}" name="{$subCampo.name}" {if $subCampo.value}checked{/if}/>
											{elseif $subCampo.tagName == "select" }
												<select style="width: auto" name="{$subCampo.name}">
													{foreach from=$subCampo.options item=option key=val}
														<option value="{$val}" {if $val==$subCampo.value}selected{/if}>{$lang.$option}</option>
													{/foreach}
												</select>
											{/if}
										{/if}
									{/foreach}
								{/if}
							</li>
						{/if}
					{/foreach}
					</ul>
				</div>
			</div>
		{/if}
		
		{if $perfil instanceof rol}
		<div id="all-options-wrap" {if ($rolactual instanceof rol)}style="display: none;"{/if} >
			<hr />
			<div>
				<button class="btn checkall" target="#all-options-div"><span><span>{$lang.marcar_desmarcar_todo}</span></span></button>
				<button class="btn toggle" target=".opciones-modulo-perfil, #all-options-div button.checkall" onclick="return false;"><span><span>{$lang.ocultar_mostrar_todo}</span></span></button>
			</div>
			<div id="all-options-div" >
				{assign var=opcionesUsuarioActivo value=$user->obtenerOpcionesDisponiblesPorGrupos($perfil)}
				<br /><br />
				<table style="width: 100%;"><tr>
				{foreach from=$opcionesUsuarioActivo item=opcion key=modo}
					<td style="width: 48%">
						<div>
							
							<strong class="ucase" style="font-size: 15px;">{$lang.$modo|default:$modo}</strong>
							<div style="padding-left: 10px">
									{foreach from=$opcion item=opcionModo key=idModulo}	
											<fieldset id="bloque-{$idModulo}-{$modo}">
												{assign var=titleString value=$user->getModuleName($idModulo)}
												<button style="float: right; margin: 0 5px 0 0; display: none;" class="btn checkall" id="option-btn-{$titleString}" target="#bloque-{$idModulo}-{$modo}"><span><span>{$lang.marcar_desmarcar_todo}</span></span></button>
												<legend class="ucase"><strong><a class="toggle" target="#option-{$titleString}, #option-btn-{$titleString}">{if isset($lang.$titleString)}{$lang.$titleString}{else}{$titleString|replace:"_":" "}{/if}</a></strong></legend>
												<div style="padding-left: 10px; display: none;" id="option-{$titleString}" class="opciones-modulo-perfil">
												{foreach from=$opcionModo item=opcionModulo key=idOpcion}	
													{assign var=string value=$opcionModulo.string}
													<input type="checkbox" name="opciones[]" value="{$opcionModulo.oid}" 
														{if $perfil->comprobarAccesoOpcion($opcionModulo.oid)}checked{/if}
													/> 
													{if isset($lang.$string)}{$lang.$string}{else}{$string}{/if}<br />
												{/foreach}
												</div>
											</fieldset>
									{/foreach}
							</div>
						</div>
					</td>
					<td style="width: 2%">&nbsp;</td>
					{/foreach}
					<td style="width: 2%">&nbsp;</td>
				</tr></table>
				<br /><br /><br />
			</div>
		</div>
		{/if}
		<input type="hidden" name="send" value="1" />
		{if isset($smarty.get.poid)}<input type="hidden" name="poid" value="{$smarty.get.poid}" />{/if}
	</form>
</div>
