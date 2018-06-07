{*

Variables
	· goto = string - donde ir cuando se envie el formulario
	· viewonly = if isset = quitar el boton de guardar y avisar
	· perfil = el objeto perfil que es modificado
*}
<div>
	<br />
	<form name="acciones-premium" action="{$smarty.server.PHP_SELF}" class="form-to-back" id="acciones-premium" {if isset($goto)}rel="{$goto}"{/if} >
		{assign var="rolactual" value=$perfil->getActiveRol()}
		{assign var="usuario" value=$perfil->getUser()}
		
		{if !isset($viewonly)}
			<div style="float: right; margin-right: 30px;">
				<button class="btn send" type="submit"><span><span>{$lang.guardar}</span></span></button>
			</div>
		{else}
			<div style="float: right; margin-right: 30px;">
				<div class="message error">No puedes modificar estos datos</div>
			</div>
		{/if}
		<h1 style="font-size: 17px; margin-left: 10px;">
			Acciones premium <span style="color: red;">{$perfil->getUserVisibleName()}</span> {if isset($usuario)} del usuario <span style="color: red;">{$usuario->getUserVisibleName()}</span>{/if}
		</h1>
		<hr />

		<div id="all-options-wrap">
			{assign var=opcionesUsuarioActivo value=$user->obtenerOpcionesDisponiblesPorGrupos(true)}
			{foreach from=$opcionesUsuarioActivo item=opcion key=modo}
				{foreach from=$opcion item=opcionModo key=idModulo}	
					{foreach from=$opcionModo item=opcionModulo key=idOpcion}

						<div class="premium-action" id="bloque-{$idModulo}-{$modo}">
							{assign var=string value=$opcionModulo.string}
							<input type="checkbox" name="opciones[]" value="{$opcionModulo.oid}" 
								{if $perfil->comprobarAccesoOpcion($opcionModulo.oid,true)}checked{/if}
							/> 
							{if isset($lang.$string)}{$lang.$string}{else}{$string}{/if}
						</div>

					{/foreach}
				{/foreach}
			{/foreach}


			<input type="hidden" name="send" value="1" />
			{if isset($smarty.get.poid)}<input type="hidden" name="poid" value="{$smarty.get.poid}" />{/if}
			<br /><br /><br />
			</div>
		</div>
	</form>
</div>
