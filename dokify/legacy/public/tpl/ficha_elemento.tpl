{if isset($papelera)&&$papelera}
	<div class="box-pre-title"> {$lang.elemento_actualmente_papelera} </div>
{/if}
{if !$touch_device}
<div class="box-title">
	{if $elemento instanceof empresa && !$elemento->needsPay()}
		<img src="{$smarty.const.RESOURCES_DOMAIN}/img/common/certified.png" title="{$lang.empresa_certificado_dokify}" height="22px" width="22px" style="vertical-align: middle" />
	{/if}

	{$lang.informacion}
</div>
{/if}
{if isset($avisosestado)}
	{foreach from=$avisosestado item=aviso}
		{assign var="string" value=$aviso.string}
		{if (isset($string))}
			<div class="{$aviso.class}" style="text-align: center">
				{if isset($lang.$string)}{$lang.$string}{else}{$string}{/if}
			</div>
		{/if}
	{/foreach}
{/if}
<form name="ficha-elemento" action="{$smarty.server.PHP_SELF}" class="ficha" id="ficha-elemento" enctype="multipart/form-data" method="POST">
	{include file=$errorpath}
	{include file=$succespath}
	{include file=$infopath}
	<div style="text-align: center; ">
		<div class="message highlight">
			<table style="table-layout: auto;" cellpadding="0">
				{assign var="userCompany" value=$user->getCompany()}
				{assign var="uid" value=$elemento->getUID()}
				{*{assign var="conteodocs" value=$elemento->getNumberOfDocumentsByStatus()}*}
				{assign var="datos" value=$elemento->getInfo(true, "ficha", $user)}
				{assign var="datos" value=$datos.$uid}
				{assign var="options" value=$elemento->getAvailableOptions($user,true, false, false)}
				{if (true === is_countable($options) && true === is_countable($datos) && count($options)<=count($datos)) || (false === is_countable($options) && false === is_countable($datos))}
					{assign var="rowspan" value=$datos|@count}
				{else}
					{assign var="rowspan" value=$options|@count}
				{/if}

				<tr>
					<td colspan="2" class="info-text"><strong>{$lang.informacion}</strong>
					</td>
					
					{if $elemento->getType()=="empleado"}
						<td rowspan="{$rowspan+3}" style="padding-right: 30px; vertical-align: top; padding-top: 0.6em;">
							{if $userCompany->hasEmployee($elemento)}
								<a class="box-it" href="empleado/asignarfoto.php?m=empleado&poid={$elemento->getUID()}" title="{$lang.asignar_fotografia}"><img style="vertical-align: middle;" class="emp-photo emp-photo-hover" src="../agd/empleado/foto.php?poid={$elemento->getUID()}&t={$time}" width="80"></a>
							{else}
								<img style="vertical-align: middle;" class="emp-photo" src="../agd/empleado/foto.php?poid={$elemento->getUID()}&t={$time}" width="80">
							{/if}
						</td>
					{/if}
					
					<td class="item-options" rowspan="{$rowspan+3}" style="padding-left: 5px;">
						<ul class="item-options">
							{if is_traversable($options)}
								{foreach from=$options item=option key=i}
									{if $option.uid_accion != 10}
										{if $option.href[0] == "#"}
											{assign var="optionclass" value="unbox-it"}
										{else}
											{assign var="optionclass" value="box-it"}
										{/if}

										<li style="white-space: nowrap;">
											<img style="vertical-align: middle;" src="{$option.img}" height="16px" width="16px"/> 
											<a href="{$option.href}" class="{$optionclass}">{$option.innerHTML}</a>
										</li>
									{/if}
								{/foreach}
							{/if}
						</ul>
					</td>
				</tr>

				{if true === is_countable($datos) && count($datos)}
				
					{foreach from=$datos item=dato key=campo}
						{if $dato}
						<tr>
							<td style="width: 100px;">
								<span class="ucase">{if isset($lang.$campo)}{$lang.$campo}{else}{$campo}{/if}</span>: 
							</td>
							<td style="width: 235px;">
								<strong class="ucase">
									{if is_array($dato)}
										{foreach from=$dato item=item}
											{$item}
										{/foreach}
									{elseif is_string($dato)}
										{$dato}
									{elseif isset($dato.innerHTML)}
										{$dato.innerHTML}
									{/if}
	
								</strong>
							</td>
						</tr>
						{/if}
					{/foreach}
					{if $elemento->getType()=="empleado" && $responsable = $elemento->getManager()}
						<tr>
							<td style="width: 100px;">
								<span class="ucase">{$lang.responsable|default:"Responsable"}</span>: 
							</td>
							<td style="width: 235px;">
								<strong class="ucase">
									{$responsable->obtenerURLFicha($responsable->getUserVisibleName())}
								</strong>
							</td>
						</tr>
					{/if}		
				{/if}
					{if is_subclass_of($elemento,'childItemEmpresa') }
						<tr><td colspan="2"><hr ></td></tr>
						<tr><td colspan="2" class="info-text">
							<strong>
								{$lang.empresas}
							</strong>
						</td></tr>
							{foreach from=$empresas item=empresa}
							{assign var="miniEmpresa" value=$empresa->getMiniArray($user)}
							<tr><td colspan="2">
								{if $miniEmpresa.estado != false}<img style="vertical-align: middle; margin-bottom: 2px;" src="{$miniEmpresa.estado.src}" title="{$miniEmpresa.estado.title}" height="12" width="12">{/if} 
								<a href="{$miniEmpresa.href}" class="box-it" style="min-width: 20em; display:inline-block;" title="{$miniEmpresa.nombre}">{$miniEmpresa.nombre|string_truncate:30}</a>
								<a href="{$miniEmpresa.hrefdocs}" class="unbox-it" title="{$lang.ver_documentos}" style="float:right;"><img style="vertical-align: middle; margin-bottom: 2px;" src="{$miniEmpresa.imgdocs}"></a>
							</td></tr>
							{/foreach}				
						{if $elemento instanceof empleado}	
							{ if isset($maquinas) }
							<tr><td colspan="2"><hr ></td></tr>
							<tr><td colspan="2" class="info-text">
								<strong>
									{$lang.maquinas}
									{assign var=cuantasMaquinas value=$maquinas->count()}
									{if $cuantasMaquinas > 3}(<a href="{$masMaquinas.href}" class="{$masMaquinas.class}" title="{$lang.ver_todas}">{$cuantasMaquinas}</a>){/if}
								</strong>
							</td></tr>
								{assign var="iter" value=0}
								{foreach from=$maquinas item=maquina}
								{assign var="iter" value=`$iter+1`}
								{if $iter<=3}
									{assign var="mini" value=$maquina->getMiniArray($user)}
									<tr><td colspan="2">
										<a href="{$mini.href}" class="box-it" style="min-width: 20em; display:inline-block;">
											{if $mini.estado != false}<img style="vertical-align: middle; margin-bottom: 2px;" src="{$mini.estado.src}" title="{$mini.estado.title}" height="12" width="12">{/if} {$mini.nombre}
										</a>
										<a href="{$mini.hrefdocs}" class="unbox-it" title="{$lang.ver_documentos}" style="float:right;"><img style="vertical-align: middle; margin-bottom: 2px;" src="{$mini.imgdocs}"></a>
									</td></tr>
								{ /if }
								{/foreach}
							{/if}
						{/if}
					{/if}

				{if $elemento instanceof documento_atributo}
					<tr>
					{assign var="datodocumento" value=$elemento->getInfo()}
						<td colspan="2">
							<a href="#buscar.php?p=0&q=tipo:tipodocumento%23{$datodocumento.uid_documento}">{$lang.ver_documento_asociado}</a> <br />
							<a href="#buscar.php?p=0&q=tipo:anexo-{$elemento->getDestinyModuleName()}%20attr:{$elemento->getUID()}">{$lang.buscar_anexados}</a>
						</td>
					</tr>
				{/if}
				
				{if $elemento->getType()=="empresa"}
					{assign var="contacto" value=$elemento->obtenerContactoPrincipal()}
					{if is_object($contacto)}
						{assign var="informacionContacto" value=$contacto->getInfo(true,true)}
						<tr>
							<td colspan="2"><strong>{$lang.contacto}</strong></td>
						</tr>
						{foreach from=$informacionContacto item=dato key=campo}
						<tr>
							<td style="width: 79px;">
								<span class="ucase">{if isset($lang.$campo)}{$lang.$campo}{else}{$campo}{/if}</span>: 
							</td>
							<td style="width: 235px;">
								<strong class="ucase">
									{if is_string($dato)}
										{$dato}
									{elseif isset($dato.innerHTML)}
										{$dato.innerHTML}
									{/if}
								</strong>
							</td>
						</tr>
						{/foreach}
					{/if}
					
					{if $elemento->setUser($user)}{/if}
					{assign var="documentosCertificacion" value=$elemento->getDocuments(false,null,false,false,true)}
					{if true === is_countable($documentosCertificacion) && count($documentosCertificacion)}
						<tr>
							<td colspan="2"><hr ></td>
						</tr>
						<tr>
							<td colspan="2"><strong>{$lang.certificacion}</strong></td>
						</tr>
						<tr>
							<td colspan="2">
								<ul>
									{foreach from=$documentosCertificacion item=documento key=i}
										{assign var="inline" value=$documento->getInlineArray($user,true)}
										{assign var="estado" value=$inline[0][1]}
										<li> 
											<a href="informaciondocumento.php?m=empresa&o={$elemento->getUID()}&poid={$documento->getUID()}" class="box-it">
											{$documento->getUserVisibleName()}
											</a> · <span class="{$estado.className}" title="{$estado.title}">{$estado.nombre}</span> </li>
									{/foreach}
								</ul>
							</td>
						</tr>
					{/if}
					
				{/if}

				{if true === is_countable($agrupadores) && count($agrupadores) }
					<tr><td colspan="2"><hr ></td></tr>
					<!-- <tr><td colspan="3" class="info-text"><strong>{$lang.agrupadores}</strong></td></tr> -->
					<tr><td colspan="2">
						{foreach from=$agrupadores item=agrupador}
							<a href="{$agrupador.href}" style="display:inline-block; padding: 5px 2px;" class="unbox-it">{$agrupador.estado}</a>
						{/foreach}
					</td></tr>
				{/if}
				{*
				<tr>
					<td colspan="2">
						<table>
							<tr>
								<td style="padding-right: 8px;" colspan="4">{$lang.conteo_documentos}: </td>
							</tr>
							<tr>
								{if count($datos)}
									{foreach from=$conteodocs item=doc key=estado}
										{if !is_numeric($estado)}
											<td style="padding: 2px 8px 2px 0;">{$estado}: {$doc}</td>
										{/if}
									{/foreach}
								{/if}
							</tr>
						</table>
					</td>
				</tr>
				*}
			</table>
		</div>

		{if $elemento instanceof usuario && $user->esStaff()}
			{if $ua = $elemento->getUserAgentData()}
				<img src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/world.png" style="vertical-align:middle" />
				{$ua->name} {$ua->version} ({$ua->platform})
			{/if}
		{/if}
	</div>



	{if $elemento instanceof usuario && $address = trim($elemento->getAddress())}
		<div class="padded">
			<div class="map" style="width:100%;height:300px;" data-address="{$address}" data-streetview="false" data-types="[]"></div>
		</div>
	{/if}

	{if isset($qr) && isset($userAddress)}
		<div id="checkin-area" class="padded" style="text-align: center; margin: 1em 3em; height:60px">

			{if $leaving}
				<div class="message succes" style="text-align: center;">
					{$lang.place_leave_registered}
				</div>
			{/if}

			{if $showQRButtons}
				{if $validCompanies > 0}
					<button class="button green xl post" href="empleado/location.php?poid={$elemento->getUID()}&amp;action=checkin" data-target="#checkin-area" style="width:100%">
						<strong>{$lang.place_access_register}</strong>
					</button>		
				{else}
					<button class="button red xl" onclick="return false;" style="width:100%; padding-left: 5px; padding-right: 5px;">
						<strong>{$lang.place_access_blocked}</strong>
					</button>
				{/if}
			{/if}

			{if $showExitButton}
				<button class="button green xl post" href="empleado/location.php?poid={$elemento->getUID()}&amp;action=checkout" data-target="#checkin-area" style="width:100%">
					<strong>{$lang.place_leave}</strong>
				</button>
			{/if}

			{if $retryQR}
				<div class="message succes" style="text-align: center;">
					{$lang.place_already_registered}
				</div>
			{/if}
		</div>
	{/if}

	{if isset($acciones) && is_array($acciones)}
		<div class="message highlight" id="reloader" style="text-align: center">
		{foreach from=$acciones item=accion}
			{assign var="string" value=$accion.string}
			<a class="{if isset($accion.class)}{$accion.class}{else}box-it{/if}" {if isset($accion.href)}href="{$accion.href}"{/if}>{if isset($lang.$string)}{$lang.$string}{else}{$string}{/if}</a>
		{/foreach}
		</div>
	{/if}

	{if ($elemento instanceof empleado) || (($elemento instanceof empresa) AND ($elemento->getUID() != $userCompany->getUID()))}
		{assign var="unsuitableItemCompanies" value=$userCompany->getUnsuitableItemClient($elemento)}
		{if true === is_countable($unsuitableItemCompanies) && count($unsuitableItemCompanies)}
			<div style="text-align: center; {if $ie}width: 550px;{/if}">
				<div class="message highlight">
					<table style="table-layout: auto;" cellpadding="0">	
						<tr>
							<td class="info-text">
								<img src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/bell_error.png" style="vertical-align: middle" />
								<strong>{$lang.title_warning}</strong>								
							</td>
						</tr>				
						<tr>
							<td colspan="2"><hr ></td>
						</tr>
						<tr>
							<td colspan="2">{$lang.elemento_no_apto_cliente}</td>
						</tr>
						<tr>
							<td colspan="2">
								<ul>
									{foreach from=$unsuitableItemCompanies item=company}
										<li>
											<strong>{$company->getUserVisibleName()}</strong>
										</li>
									{/foreach}
								</ul>
							</td>
						</tr>
					</table>
				</div>
			</div>
		{/if}
	{/if}
	
</form>
<div class="cboxButtons">
	{if $elemento instanceof empresa && $user->esStaff() && !$elemento->compareTo($user->getCompany())}
		<div style="float:left">
			<a class="btn changeprofile" to="{$elemento->getUID()}" rel="company"><span><span><img src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/user_go.png" /> Go</span></span></a>	
		</div>
	{/if}

	{if $elemento instanceof empleado}
	<div style="float:left">
		{if $userCompany->hasEmployee($elemento)}
			<a class="btn" target="async-frame" href="../qr.php?selected[]={$elemento->getUID()}"><span><span>
				<img src="{$smarty.const.RESOURCES_DOMAIN}/img/qr.png" />
				QRCode
			</span></span></a>
		{/if}

		{if $user instanceof usuario}
			<a class="btn box-it" href="aptitud.php?oid={$elemento->getUID()}&m={$elemento->getModuleName()}"><span><span><img src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/key.png" /> {$lang.opt_aptitud} </span></span></a>
		{/if}

		<a class="btn unbox-it" href="#logui.php?m=empleado&amp;poid={$elemento->getUID()}"><span><span>
			<img src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/time_go.png" alt="l" width="12px" height="12px" />
			{$lang.ver_log}
		</span></span></a>
	</div>
	{/if}

	{include file=$tpldir|cat:'button-list.inc.tpl'}
	{if $elemento instanceof usuario && $user->esStaff()}
		<button class="btn simular" value="{$elemento->getUID()}"><span><span>{$lang.navegacion_simulada}</span></span></button>
	{/if}

	{if $elemento instanceof empresa}
        {if !$userCompany->compareTo($elemento)}
            <div style="float:left">
                    <a class="btn box-it" href="aptitud.php?oid={$elemento->getUID()}&m={$elemento->getModuleName()}"><span><span><img src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/key.png" /> {$lang.opt_aptitud} </span></span></a>
            </div>
        {/if}
    {/if}

	{if $elemento instanceof empleado && $user->esStaff()}
		<a class="btn" href="empleado/simular.php?poid={$elemento->getUID()}"><span><span>{$lang.navegacion_simulada}</span></span></a>
	{/if}

	<!-- {if $elemento instanceof maquina}
		{assign var="maquinas" value=$userCompany->obtenerMaquinas()}
		{if $maquinas->contains($elemento)}
			<div style="float:left">
				<a class="btn box-it" href="aptitud.php?oid={$elemento->getUID()}&m={$elemento->getModuleName()}"><span><span><img src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/key.png" /> {$lang.opt_aptitud} </span></span></a>
			</div>
		{/if}
	{/if} -->
	<div class="clear"></div>
</div>
