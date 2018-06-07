<div class="box-title">
	{$lang.contactos} - {$empresa->getUserVisibleName()}
</div>
	<div style="width: 600px;">
		{include file=$errorpath}
		{include file=$succespath}
		{include file=$infopath}

		{assign var="contactos" value=$empresa->obtenerContactos()}
		{if count($contactos)}
			{foreach from=$contactos item=contacto}
				{assign var="opciones" value=$contacto->getAvailableOptions($user, true)}
				<div class="box-message-block contact-block">
						<h2 {if $contacto->esPrincipal()}id="contacto-principal"{/if} class="ucase">{$contacto->getUserVisibleName()} {if $contacto->esPrincipal()}<img src='{$resources}/img/famfam/accept.png' />{/if}</h2>
						<table style="margin: 0 0 0 10px; width: 100%; table-layout: fixed;">
							<tr>
								<td class="form-colum-description"><strong>{$lang.referencia}:</strong></td><td class="form-colum-value">{$contacto->obtenerDato("referencia")}</td>
							</tr>
							<tr> 
								<td class="form-colum-description"><strong>{$lang.email}:</strong></td><td class="form-colum-value">{$contacto->obtenerDato("email")}</td>
							</tr>
							<tr>
								<td class="form-colum-description"><strong>{$lang.telefono}:</strong></td><td class="form-colum-value">{$contacto->obtenerDato("telefono")} · {$contacto->obtenerDato("movil")}</td>
							</tr>
							{if $accessContact || $user->esStaff()}
								<tr>
									{if !$user->isAgent()}
										<td colspan="2">
											<div class="toggle padded" rel="slideToggle" target="#plantillas-contacto-{$contacto->getUID()}" style="padding-left:0px">
												<strong class="link">{$lang.opciones_extra}</strong>
											</div>
											<div id="plantillas-contacto-{$contacto->getUID()}" style="display:none">
												<ul class="" >
												{assign var="ids" value=$contacto->getArrayPlantillas(true)}
												{foreach from=$plantillas item=plantilla}
													<li> 
														<input type="checkbox"
														{if !$contacto->esPrincipal() 
															|| 
															in_array($plantilla->getUID(), $templatesToAvoid)
														}
															class="post"
															href="empresa/contacto.php?action=plantilla&oid={$contacto->getUID()}&ref={$plantilla->getUID()}"
														{/if}
														{if in_array($plantilla->getUID(), $ids)
														|| ($contacto->esPrincipal()
															&& (!in_array($plantilla->getUID(), $templatesToAvoid)))}
															checked
														{/if} 
														{if $contacto->esPrincipal()&& !in_array($plantilla->getUID(), $templatesToAvoid) }
															disabled
														{/if}
														/> 
														{assign var="name" value="plantillaemail_"|cat:$plantilla->getName()}
														{$lang.$name|default:$name}
													</li>
												{/foreach}
												</ul>
												{if $contacto->esPrincipal()}<strong>
													{$lang.contacto_principal_aviso}
												</strong>{/if}
											</div>
										</td>
									{/if}
								</tr>
							{/if}
						</table>
						{if $accessContact || $user->esStaff()}
							<div style="padding: 8px">
								<ul class="inline-options">
								{foreach from=$opciones item=opt}
									<li><img src="{$opt.img}" /> <a class="{if $opt.class}{$opt.class}{else}box-it{/if}" href="{$opt.href}">{$opt.innerHTML}</a></li>
								{/foreach}
								</ul>
							</div>
						{/if}
					
				</div>
			{/foreach}
		{else}
			<div class="message highlight cbox-content">
				{$lang.no_hay_contactos}
			</div>
		{/if}
		</div>
		<div class="cboxButtons">	
			{if $user->esStaff() && !$user->isAgent()}
				<div style="float:left">
					<button class="btn box-it" href="importacion.php?m=contactoempresa&poid={$smarty.request.poid}">
						<span><span> <img src="{$resources}/img/famfam/add.png" /> {$lang.opt_importar} </span></span>
					</button>
				</div>
			{/if}
			{if $accessContact || $user->esStaff()}
				<button class="btn box-it" href="empresa/nuevocontacto.php?poid={$smarty.get.poid}">
					<span><span> <img src="{$resources}/img/famfam/add.png" /> {$lang.crear_contacto} </span></span>
				</button>
			{/if}
		</div>


