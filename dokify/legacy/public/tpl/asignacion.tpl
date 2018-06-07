{*
Descripcion
	Plantilla para uso via json ya que no contiene html ni body
	Se utiliza el metodo getHTML en vez de Display para obtener los datos

En uso actualmente
	-	/agd/asignacion.php

Variables
	· $secciones - array( Objecto Agrupamiento  ) = En definitiva cada bloque con elementos que se mostrará
	· $columnas - int() = define el numeri de columnas en que se distribuiran los documentos
	· $elemento - Objeto al que se asignan los elementos
	· $bloquear - [ true | false ] - Bloqueara el acceso
	· $ocultar - [ true | false ] - Mostrar elementos disponibles
	· $empresa - Objeto empresa del usuario que visualiza
*}	
{assign var="options" value=$elemento->getAvailableOptions($user,true,0,false)}


<div class="asignacion-elementos">

<form action="{$smarty.server.PHP_SELF}" class="async-form reload" method="POST">
	
	{if ($empresa->esCorporacion())}
		{assign var="empresasCorporacion" value=$empresa->obtenerEmpresasInferiores()}
	{/if}

	{if ($empresa->perteneceCorporacion())}
		{assign var="corp" value=$empresa->perteneceCorporacion()}
		{assign var="empresasCorporacion" value=$corp->obtenerEmpresasInferiores()}
	{/if}
	
	{if !count($secciones)}
		<div class="option-title empty">
			No hay asignaciones para este elemento
			{if $visibilidad=$user->accesoAccionConcreta($elemento, 52)}
				<br />
				<a href="{$visibilidad.href}&poid={$elemento->getUID()}" class="box-it">Tu empresa no tiene agrupamientos asignados o no trabajas para ningun cliente</a>
			{/if}
		</div>

		<div style="float:left;width:100%;margin-left:45%;padding-bottom:40px">

			{if $elemento instanceof empresa && $elemento->perteneceCorporacion($user) && isset($empresasCorporacion) && $empresasCorporacion->contains($elemento)  && $user->accesoAccionConcreta("empresa","35")}
				<button class="btn box-it" title="Asignar agrupamientos" href="empresa/asignaragrupamientos.php?poid={$elemento->getUID()}">
					<span><span><img src="{$res}/img/famfam/application_side_expand.png" />&nbsp;{$lang.opt_asignar_agrupamientos}</span></span>
				</button>
			{/if}
		</div>
	{else}
		<div class="keep-visible">
			{if is_callable(array($elemento,"getStatusImage")) && $elemento instanceof solicitable}
				{assign var="status" value=$elemento->getStatusImage($user)}
				<div class="elemento-status-bar elemento-status-{$status.color}">
					{$status.title|default:$lang.sin_asignar}
				</div>
			{elseif $elemento instanceof usuario && !(count($elemento->obtenerAgrupamientosWithFilter())) && $elemento->isViewFilterByGroups()}
				<div class="elemento-status-bar elemento-status-red">
					{$lang.filter_group_user_acces}
				</div>
			{else}
				<br />
			{/if}

			<div style="float: right; margin-right: 4%;">
				{if !is_ie6()}
				<div style="height: 21px; float:left; margin: 0 10px 0 0; padding-top:5px; {if is_ie()}width: 300px;{/if}">

					{assign var="showdocs" value=true}
					{if $elemento instanceof solicitable && $opt = $user->accesoAccionConcreta($elemento, "documentos")}
						{assign var="showdocs" value=false}
						<div style="float:left; padding:0 1.5em; line-height: 21px;">
							<img src="{$opt.icono}" style="vertical-align:middle" />
							<a href="{$opt.href}&amp;poid={$elemento->getUID()}" style="vertical-align:middle">{$lang.documentos}</a>
						</div>
					{/if}

					{if $empresa->esCorporacion() && $elemento instanceof empresa && $elemento->perteneceCorporacion($user) && isset($empresasCorporacion) && $empresasCorporacion->contains($elemento)  && $user->accesoAccionConcreta("empresa","35")}
						<div style="float:left;padding-right:10px;">
							<button class="btn box-it" title="Asignar agrupamientos" href="empresa/asignaragrupamientos.php?poid={$elemento->getUID()}">
								<span><span><img src="{$res}/img/famfam/application_side_expand.png" />&nbsp;{$lang.opt_asignar_agrupamientos}</span></span>
							</button>
						</div>
					{/if}

					<div class="select simple-select" style="float:left">
						<ul style="height: 21px; overflow:hidden;">
							<li style=""><div><span class="arrow"></span>{$lang.opciones}</div></li>
							{if is_array($options) }
								{foreach from=$options item=option }
									{if $option.uid_accion != 20 && ($option.uid_accion != 3 || $showdocs)}
										{if $option.href[0] == "#"}
											{assign var="optionclass" value="unbox-it"}
										{else}
											{assign var="optionclass" value="box-it"}
										{/if}

										<li>
											<div>
												<span>
													<img class="option-img" src="{$option.img}" style="vertical-align: middle;"/>
													<a href="{$option.href}" class="{$optionclass}"> {$option.innerHTML}</a>
												</span>
											</div>
										</li>
									{/if}
								{/foreach}		
							{/if}
						</ul>
					</div>
				</div>
				{/if}
				{if $elemento instanceof agrupador}
					<button class="btn" href="#agrupamiento/reverse.php?poid={$elemento->getUID()}" style="float:left; margin-top:5px"><span><span>{$lang.vista_jerarquica}</span></span></button>
				{/if}

				{if $elemento instanceof empresa }
					{assign var="empresaElemento" value=$elemento}
				{else}
					{assign var="empresaElemento" value=$elemento->getCompany($user)}
				{/if}

				{if isset($bloquear) && $bloquear || (($elemento instanceof empleado || $elemento instanceof maquina) && 
					( $user->esStaff() || (!$empresaElemento->compareTo($empresa) && !$empresaElemento->perteneceCorporacion() && $empresa->esCorporacion())))}
					{if $elemento instanceof empleado || $elemento instanceof maquina}
						{assign var=empresaItem value=$elemento->getCompany()}
						{if !$empresaItem->compareTo($user->getCompany())}
							<li class="menu-element-left">
								<button class="btn detect-click" name="suggest"><span><span><img src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/email_go.png" /> {$lang.sugerir}</span></span></button>
							</li>
						{/if}
						{if $solicitudes = $elemento->solicitudesPendientes($user)}
							{foreach from=$solicitudes item=solicitud}

								{assign var=solicitante value=$solicitud->getUser()}
								{assign var=fecha value=$solicitud->obtenerDato('fecha')|strtotime|date_format:'%d/%m/%Y %H:%M'}
								<li class="menu-element-left">
									<a class="btn box-it" title="Solicitud de asignación pendiente del día {$fecha} realizada por @{$solicitante->getUserName()}" href="sugeriragrupador.php?m={$elemento->getModuleName()}&poid={$elemento->getUID()}&request={$solicitud->getUID()}" ><span><span><img src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/email_error.png" style="vertical-align: middle;" /></span></span></a>
								</li>
							{/foreach}
						{/if}
					{else}
						<div class="message error">No puedes modificar estos datos</div>
					{/if}
				{/if}


				{if $bloquear==false}

					{if $user->esAdministrador() && $elemento instanceof empresa }
						<li class="menu-element-left">
							<button class="btn box-it" title="Asignaciones guardadas" href="empresa/asignacionguardada.php?poid={$elemento->getUID()}"><span><span><img src="{$res}/img/famfam/disk.png" /></span></span></button>
						</li>
					{/if}
					{if $elemento instanceof empresa}
						{if $asignacionmasiva && $user->accesoAccionConcreta("empleado", "asignaciones", 0, "empresa")}
						<li class="menu-element-left">
							<div class="auto-trigger line-block">
								{assign var=config_empleados value=$user->configValue("asig_empleados")}
								<button class="btn send detect-click showload confirm" name="asignacion" value="empleado" {if is_ie8()}style="margin-right: 10px;"{/if}><span><span>
								{$lang.asignar_a_empleados}
								</span></span></button>		
								<input title="{$lang.recordar_y_aplicar}" type="checkbox" name="config_asig_empleados" class="post" href="asignacion.php?m=empresa&comefrom=asig_empleados" {if $config_empleados }checked{/if} />					
								{if is_ie()}&nbsp;&nbsp;{/if}
							</div>
						</li>
						{/if}

						{if $asignacionmasiva && $user->accesoAccionConcreta("maquina", "asignaciones", 0, "empresa")}
						<li class="menu-element-left">
							<div class="auto-trigger line-block">			
								{assign var=config_maquinas value=$user->configValue("asig_maquinas")}
								<button class="btn send detect-click showload confirm" name="asignacion" value="maquina" {if is_ie8()}style="margin-right: 10px;"{/if}><span><span>
								{$lang.asignar_a_maquinas}							
								</span></span></button>	
								<input title="{$lang.recordar_y_aplicar}" type="checkbox" name="config_asig_maquinas" class="post" href="asignacion.php?m=empresa&comefrom=asig_maquinas" {if $config_maquinas}checked{/if} />
								{if is_ie()}&nbsp;&nbsp;{/if}	
							</div>
						</li>
						{/if}
					{/if}
					{ if $user->accesoAccionConcreta($elemento,153) }
					<li class="menu-element-left">
						<button class="btn send showload detect-click {if $elemento instanceof agrupador}confirm{/if}" type="submit" name="save"><span><span>
						<img src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/disk.png" />
						{$lang.guardar}
						</span></span></button>
					</li>
					{/if}
					
				{/if}
			</div>

			<span class="option-title">{$lang.asignar_elementos} &raquo; {$elemento->getUserVisibleName()}</span>
		</div>
		{if isset($needReviewOrganizations) && count($needReviewOrganizations)}
			<div style="float:right; width:100%; margin-top:10px">
				<span style="float:right; margin-right:4%" class="message error">
					{$lang.expl_mandatory_group}
				</span>
			</div>
		{/if}

		<br /><br />

		{if !isset($columnas)}
			{assign var="columnas" value=1}
		{/if}

		{assign var="bucle" value=1}
		{assign var="loop" value=0}

		{foreach from=$secciones item=grupo key=uidempresa}
			{new result="empresagrupo" type="empresa" uid=$uidempresa}

			{if !count($grupo)}
				{php}continue;{/php}
			{/if}

			{assign var="empresasSupElemento" value=$empresaElemento->obtenerEmpresasSolicitantes()}
			{assign var="empresasSupElemento" value=$empresasSupElemento->merge($empresa)}

			{assign var="empresasSup" value=$empresa->obtenerEmpresasSolicitantes()}
			{assign var="empresasSup" value=$empresasSup->merge($empresa)}

			<div class="asignacion-body">
				<div class="nombre-cliente-asignacion">
					<a href="#" class="toggle" target="#bloque-asignacion-{$loop}">{$empresagrupo->getUserVisibleName()}</a>
				</div>
				<table style="width: 98%; {if $empresa->getUID()!=$empresagrupo->getUID() && (count($secciones) > 1)}display:none;{/if}" id="bloque-asignacion-{$loop}" class="variable-column-table">

					{assign var="i" value=0}
					{foreach from=$grupo item=agrupadores key=objectstring}
						{assign var="seccion" value='agrupamiento::factory'|call_user_func:$objectstring}
					
						{assign var="editable" value=$seccion->isEditable($user, $elemento)}
						{assign var="suggest" value=$seccion->mustBeSuggested($user, $elemento)}

						{if $agrupadores.disponible == "readonly"}
							{assign var="editable" value=false}
								
						{/if}

						{assign var="total" value=$grupo|@count}

						{assign var="applyOnDemand" value=$elemento->canApplyOnDemand($seccion, $user)}

						{*{if ($bucle==1)}<tr>{/if}*}
						{if (is_countable($agrupadores.disponible) && count($agrupadores.disponible)) || (is_countable($agrupadores.asignado) && count($agrupadores.asignado))}
							<td>
								<div class="section-title">
									<span style="float: right;{if $seccion->hidden}margin-right:20px;{/if}" class="light">
									{$lang.buscar_en_lista} <input type="text" class="find-html" search="{$lang.buscando_elemento_s}" target="#e-d-{$seccion->getUID()}, #e-a-{$seccion->getUID()}" rel="li"/>
									</span>

									{assign var="seccionoptions" value=$seccion->getAssignOptionsFor($elemento, $user)}
									<span {if count($seccionoptions)}class="option-block"{/if}> <span class="ucase title">{$seccion->getUserVisibleName()}{*({$seccion->getUID()})*}</span>

									{if $seccion->isMandatory() && isset($needReviewOrganizations) && $needReviewOrganizations->contains($seccion)}
										<span class="light help red" title="{$lang.mandatory_groups_needed}">{$lang.obligatorio}</span>
									{/if}
										{if count($seccionoptions)}
											{if !$ocultar}<img src="{$res}/img/blank.gif" />
											<div style="display:none">
												<ul>
													{foreach from=$seccionoptions item=option key=i}
														<li> 
															{$option.text}
															{if $option.tagName=="input"}
																<{$option.tagName} class="{$option.className}" type="{$option.type}" href="{$option.href}" {if $option.checked}checked{/if} {if isset($option.innerHTML)}value="{$option.innerHTML}"{/if} /> 
															{else}
																<{$option.tagName} class="{$option.className}" type="{$option.type}" href="{$option.href}" {if $option.checked}checked{/if}>{$option.innerHTML}</{$option.tagName}>
															{/if}
														</li>
													{/foreach}
												</ul>
											</div>
											{/if}
										{/if}
									</span>
								</div>
							<br />
							<table class="asignar" style="{if $seccion->hidden}width: 98%;{else}width: 100%;{/if}">
								<thead>
									{if !$applyOnDemand  }
									<tr>
										{if !$ocultar && $agrupadores.disponible != "hidden" && $agrupadores.disponible != "readonly"}<th > {if $editable}<a class="light checkall" target="#e-d-{$seccion->getUID()}">{$lang.marcar_desmarcar}</a>{/if} {$lang.disponibles} </th> 
										<th style="width: 40px;"> </th>{/if}
										<th {if $ocultar} colspan="3" {/if} > {if $editable && !$ocultar }<a class="light checkall" target="#e-a-{$seccion->getUID()}" >{$lang.marcar_desmarcar}</a>{/if} {$lang.asignados} </th>
									</tr>
									{else}
									<tr>
										<th style="text-align: left;">
											<input type="text" class="fast-add" maxlength="250" target="#e-a-{$seccion->getUID()}" href="fastadd.php?m=agrupamiento&poid={$seccion->getUID()}&mode=agrupador&o={$elemento->getUID()}&assign={$elemento->getModuleName()}"/> {$lang.opt_crear_nuevo} 
										</th>
									</tr>
									{/if}
								</thead>
								<tr>				
									{if (!$applyOnDemand ) && $agrupadores.disponible != "hidden" && $agrupadores.disponible != "readonly"}
										{if !$ocultar}
											<td class="field-list">

												<ul id="e-d-{$seccion->getUID()}">
													{if isset($agrupadores.disponible) && count($agrupadores.disponible)}
														{foreach from=$agrupadores.disponible item=item}
															{if is_object($item)}
																{if $elemento->getType() == "agrupador" && $item->getUID() == $elemento->getUID()}

																{else}
																	<li name="{$item->getUID()}">
																		<label for="lbl-{$item->getUID()}">
																			{if $suggest || $editable}<input type="hidden" name="e-d-{$seccion->getUID()}[]" value="{$item->getUID()}" />{/if}
																			<input type="checkbox" class="line-assign" id="lbl-{$item->getUID()}" {if !$editable && !$suggest}disabled{/if}/> 
																			<span class="ucase">{$item->getUserVisibleName()}</span>
																		</label>
																	</li>
																{/if}
															{else}
																<h3>{$item}</h3>
															{/if}
														{/foreach}
													{/if}
												</ul>
											</td>
								
											<td style="border: 0px;text-align:center;"><button type="button" class="btn list-move" style="margin-bottom: 2px;" rel="#e-a-{$seccion->getUID()}" target="#e-d-{$seccion->getUID()}" {if !$editable && !$suggest}disabled{/if}><span><span> &nbsp; &laquo; &nbsp; </span></span></button><br /><button type="button" class="btn list-move" rel="#e-d-{$seccion->getUID()}" target="#e-a-{$seccion->getUID()}" {if !$editable && !$suggest}disabled{/if}><span><span> &nbsp; &raquo; &nbsp; </span></span></button> 
											</td>
										{/if}
									{/if}
									{if !$ocultar}
									<td class="field-list {if $applyOnDemand }single{/if}">
									{else}
									<td class="field-list {if $applyOnDemand }single{/if}" colspan="3">
									{/if}
										<div>
											{if $elemento instanceof categorizable }
												{assign var="acciones" value=$elemento->obtenerAccionesRelacion($seccion,$user)}
												{if count($acciones)}
												<ul style="display: none; float: right;" id="e-a-{$seccion->getUID()}-op" class="asignar-options">
													{foreach from=$acciones item=accion}
													<li>
														<label>
															<span class="ucase"><a href="{$accion.href|default:''}" class="{$accion.className|default:''}">{if $accion.img}<img src=" {$accion.img}" />{/if} {$accion.innerHTML}</a></span>
														</label>
													</li>
													{/foreach}
												</ul>
												{/if}
											{/if}
											<ul id="e-a-{$seccion->getUID()}">
												{if isset($agrupadores.asignado) && count($agrupadores.asignado)}
													{foreach from=$agrupadores.asignado item=item}
														{if is_object($item)}
															{if $item->getType() == "agrupador" && $elemento->getType() == "agrupador" && $item->getUID() == $elemento->getUID() }

															{else}
														
																<li id="id-li-{$item->getUID()}-{$seccion->getUID()}" name="{$item->getUID()}" {if $elemento instanceof empresa && $item->esJerarquia()} data-ishierarchy="yes"{/if}>
																	<label for="lbl-{$item->getUID()}">

																		<span class="relation-options" style="white-space:nowrap;">
																			{if $elemento instanceof empresa}
																				<a href="#buscar.php?p=0&q=asignado:{$item->getUID()}%20tipo:maquina%20empresa:{$elemento->getUID()}"><img src="{$res}/img/famfam/car.png" title="{$lang.maquinas}" /></a>

																				<a href="#buscar.php?p=0&q=asignado:{$item->getUID()}%20tipo:empleado%20empresa:{$elemento->getUID()}"><img src="{$res}/img/famfam/group.png" title="{$lang.empleados}" /></a>
																			{/if}

																			{if count($acciones) && $editable}
																				<img class="box-it" href= "asignacion.php?m={$elemento->getType()}&poid={$elemento->getUID()}&oid={$item->getUID()}&o={$seccion->getUID()}&tab=duracion" title="{$lang.duracion}" src="{$res}/img/famfam/clock_add.png"/>

																				<img src="{$res}/img/famfam/add.png" title="{$lang.opciones_relacion}" class="slide-list link" name="{$item->getUserVisibleName()}" href="{$item->getUID()}" rel="#e-a-{$seccion->getUID()}" target="#e-a-{$seccion->getUID()}-op" />

																				{if $applyOnDemand }
																					<span class="update" target="#val-e-a-{$seccion->getUID()}-{$item->getUID()}" rel="name" update="e-d-{$seccion->getUID()}[]">
																						<img src="{$res}/img/famfam/delete.png" title="{$lang.eliminar}" class="toggle" target="#id-li-{$item->getUID()}-{$seccion->getUID()}" />
																					</span>
																				{/if}
																			{/if}
																		</span>
																		

																		{if $editable}<input type="hidden" id="val-e-a-{$seccion->getUID()}-{$item->getUID()}" name="e-a-{$seccion->getUID()}[]" value="{$item->getUID()}" />{/if}
																		{if $item->esBloqueado($elemento)}
																			<img src="{$res}/img/famfam/lock_delete.png" class="item" title="{$lang.bloqueado}" />
																		{else}
																			<input type="checkbox" class="line-assign" id="lbl-{$item->getUID()}" {if !$editable || $bloquear }disabled{/if}/> 
																		{/if}
																		<span class="ucase">
																			{$item->getUserVisibleName()} 
																			{if $date = $elemento->getAssignExpirationDate($item)}
																				<span class="light" title="{$lang.la_asignacion_se_eliminara}">{$date}</span>
																			{/if}

																			{if ($item->auto)}(A){/if} 
																			{if $item->rebote&&($rebote=$item->rebote)}
																				<a class="highlight-target" target="li[name='{$rebote->getUID()}']" style="color:#3F68B6;" title="{$rebote->getTypeString()} - {$rebote->getUserVisibleName()}">(R)</a>
																			{/if} 
																		</span>
																	</label>
																</li>
															{/if}
														{else}
															<h3> {$item}</h3>
														{/if}
													{/foreach}
												{else}
													<div class="sinasignar e-{$seccion->getUID()}">{$lang.nada_asignado}</div>
												{/if}
											</ul>
										</div>
									</td>
								</tr>
								<tr><td {if (!$applyOnDemand) && $agrupadores.disponible != "hidden" && $agrupadores.disponible != "readonly"}colspan="3"{/if} style="border: 0px"></td></tr>
							</table>
							</td>

							{if ($bucle==$columnas)}
								{assign var="bucle" value=1}
								<tr>
									{if $i<ceil($total/$columnas)}
										<tr><td colspan="{$columnas}" class="margenize"><hr /></td></tr>
									{/if}
							{else}	
								{assign var="bucle" value=$bucle+1} 
							{/if}
						{/if}
						{*assign var="i" value=$i+1}*}
					{/foreach}
					{assign var="bucle" value=1} 
					{assign var="loop" value=$loop+1} 
				</table>
			</div>
		{/foreach}


		<input type="hidden" name="send" value="1" />
		{if isset($smarty.get.poid)}<input type="hidden" name="poid" value="{$smarty.get.poid}" />{/if}
		{if isset($smarty.get.m)}<input type="hidden" name="m" value="{$smarty.get.m}" />{/if}
		{if isset($smarty.request.return)}<input type="hidden" name="return" value="{$smarty.request.return}" />{/if}
		{if isset($smarty.request.comefrom)}<input type="hidden" name="comefrom" value="{$smarty.request.comefrom}" />{/if}
		<br /><br />
	{/if}
</form>
</div>