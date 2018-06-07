<div class="box-title">
	{$elemento->getUserVisibleName()}

	{assign var="modulo" value=$elemento->getType()}
	{assign var="tabs" value="$modulo::fieldTabs"|call_user_func:$user}
	<div class="tabs">
		{foreach from=$tabs item=tab key=i}
			{assign var="tabname" value=$tab->name}
			{assign var="icon" value=$smarty.const.RESOURCES_DOMAIN|cat:"/img/"|cat:$tab->icon}
			<div class="box-tab {if !$i}selected{/if}" rel="#tab-{$tabname}"><img src="{$icon}" height="16px" widht="16px" /> {$lang.$tabname|default:$tabname}</div>
		{/foreach}
	</div>
</div>
<form action="../agd/configurar/modificar.php?send=1&inline=true&m={$elemento->getType()}&poid={$elemento->getUID()}" method="{$smarty.server.REQUEST_METHOD}" onsubmit="return false;">
	<div id="tabs-content" class="cbox-content">
		{foreach from=$tabs item=tab key=i}
			{assign var="tabname" value=$tab->name}

			<div id="tab-{$tabname}" style="{if $i}display:none;{/if}">
				{assign var="campos" value=$elemento->getPublicFields(true, "edit", $user, $tab)}

				<table class="agd-form">
				{foreach from=$campos item=campo key=nombre}
					{if !$campos instanceof FieldList || ( $campos instanceof FieldList && ($open = $campos->openLine($campo)) )}
						<tr id="form-line-{$nombre}" {if $open && $campo instanceof FormField && $campos->endLine($campo) && $campo->isHidden($campos)}style="display:none;"{/if}>
					{/if}
						<td class="form-colum-description" {if $campo.search}style="vertical-align: bottom;padding-bottom:0.8em;"{/if}> {if !$campo instanceof FormField} {if dump($campo)}{/if} {/if} {$campo->getInnerHTML()} </td>
						<td class="form-colum-separator"></td>
						<td class="form-colum-value" style="vertical-align: middle;" {if $campo.affects}data-affects="{$campo.affects}"{/if} {if $campo.parts}data-parts="{$campo.parts}"{/if} {if $campos instanceof FieldList}colspan="{$campos->getMinColSpan($campo)}"{/if}>
							{include file=$tpldir|cat:'form/form_parts_live.inc.tpl'}
						</td>
					{if  !$campos instanceof FieldList || ( $campos instanceof FieldList && $campos->endLine($campo) )}
					</tr>
					{/if}

					{if $campo.hr}<tr><td colspan="3"><hr /></td></tr>{/if}
				{/foreach}
				</table>

			</div>
		{/foreach}
	</div>
	<div class="cboxButtons">
		<a class="btn box-it" href="../agd/ficha.php?m=empleado&poid={$elemento->getUID()}"><span><span>{$lang.volver}</span></span></a>
	</div>
</form>









{*
<form name="ficha-elemento" action="{$smarty.server.PHP_SELF}" class="ficha" id="ficha-elemento" enctype="multipart/form-data" method="POST">
	{include file=$errorpath}
	{include file=$succespath}
	{include file=$infopath}
	<div style="text-align: center; {if $ie}width: 550px;{/if}">
		<div class="message highlight">
			<table style="table-layout: auto;">
				{assign var="uid" value=$elemento->getUID()}
				{assign var="conteodocs" value=$elemento->getNumberOfDocumentsByStatus()}
				{assign var="datos" value=$elemento->getInfo(true, "ficha", $usuario)}
				{assign var="datos" value=$datos.$uid}
				{assign var="options" value=$elemento->getAvailableOptions($usuario,true, false, false)}
				{if count($options)<=count($datos)}
					{assign var="rowspan" value=$datos|@count}
				{else}
					{assign var="rowspan" value=$options|@count}
				{/if}
				<td>				
				<tr>
					<td colspan=2><strong>{$lang.informacion}</strong>
					</td>
					
					{if $elemento->getType()=="empleado"}
						<td rowspan="{$rowspan+3}" style="padding-right: 30px; vertical-align: top; padding-top: 0.6em;">
							
							<a class="box-it" href="empleado/asignarfoto.php?m=empleado&poid={$elemento->getUID()}" title="{$lang.asignar_fotografia}"><img style="vertical-align: middle;" class="emp-photo" src="../agd/empleado/foto.php?poid={$elemento->getUID()}&t={$time}" width="80"></a>
						</td>
					{/if}
					
					<td rowspan="{$rowspan+3}" style="padding-left: 5px;">
						<ul>
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

				{if count($datos)}
				<td>
					{foreach from=$datos item=dato key=campo}
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
					</td>
				{/if}
					
				
				<tr>
					{if $elemento->getType()=="empleado"}	
						<td colspan="3"><hr ></td>
					{else}		
						<td colspan="2"><hr ></td>
					{/if}
				</tr>

				
								
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
					
					{assign var="documentosCertificacion" value=$elemento->getDocuments(false,null,false,false,true)}
					{if count($documentosCertificacion)}					
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
										{assign var="estado" value=$inline[0][0]}
										<li> 
											<a href="informaciondocumento.php?m=empresa&o={$elemento->getUID()}&poid={$documento->getUID()}" class="box-it">
											{$documento->getUserVisibleName()}
											</a> · <span class="stat stat_{$estado.estadoid}">{$estado.estado}</span> </li>
									{/foreach}
								</ul>
							</td>
						</tr>
					{/if}

					
				{/if}
				
			</table>
		</div>
	</div>
	{if isset($acciones) && is_array($acciones)}
		<div class="message highlight" id="reloader" style="text-align: center">
		{foreach from=$acciones item=accion}
			{assign var="string" value=$accion.string}
			<a class="{if isset($accion.class)}{$accion.class}{else}box-it{/if}" {if isset($accion.href)}href="{$accion.href}"{/if}>{if isset($lang.$string)}{$lang.$string}{else}{$string}{/if}</a>
		{/foreach}
		</div>
	{/if}
</form>
<div class="cboxButtons">
	{include file=$tpldir|cat:'button-list.inc.tpl'}
	{if $elemento->getType()=="usuario" && ($user->esStaff())}
		<button class="btn simular" value="{$elemento->getUID()}"><span><span>Iniciar Navegacion Simulada</span></span></button>
		<button class="btn chat unbox-it" to="{$elemento->getUserName()|strtolower}"><span><span>Iniciar Chat</span></span></button>
	{/if}
	{if $elemento->getType()=="empleado" && ($user->esStaff())}
		<a class="btn" target="_blank" href="empleado/simular.php?poid={$elemento->getUID()}">Iniciar Navegacion Simulada</a>
	{/if}
</div>
*}
